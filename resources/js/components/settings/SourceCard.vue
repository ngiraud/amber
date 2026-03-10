<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Loader2, XIcon } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { SourceDefinition, SourceFieldDefinition } from '@/types';

const props = defineProps<{
    source: SourceDefinition;
}>();

// ── Form ────────────────────────────────────────────────────────────────────
//
// Inertia's useForm handles enabled + form meta (processing, errors).
// Dynamic field values live in a plain reactive record to avoid fighting
// against FormDataType<T> which doesn't accept unknown in index signatures.

const form = useForm({ enabled: Boolean(props.source.config.enabled) });

const showForm = ref(Boolean(props.source.config.enabled));

// Field values as a plain reactive record — honest about being dynamic
const fieldValues = reactive<Record<string, unknown>>(initFieldValues());

// form.errors is keyed to { enabled } but the server returns dynamic keys.
// Cast once here so the template doesn't need individual workarounds.
const errors = computed(() => form.errors as Record<string, string | undefined>);

function initFieldValues(): Record<string, unknown> {
    const data: Record<string, unknown> = {};

    for (const field of props.source.fields) {
        const raw = props.source.config[field.name];

        if (field.type === 'email-list') {
            data[field.name] = Array.isArray(raw) ? raw.join(', ') : '';
        } else if (field.type === 'string-list') {
            const sep = field.separator === ',' ? ', ' : (field.separator ?? '\n');
            data[field.name] = Array.isArray(raw) ? (raw as string[]).join(sep) : '';
        } else if (field.type === 'number') {
            data[field.name] = typeof raw === 'number' ? raw : 0;
        } else {
            data[field.name] = typeof raw === 'string' ? raw : '';
        }
    }

    return data;
}

function buildPayload(): Record<string, unknown> {
    const result: Record<string, unknown> = { enabled: form.enabled };

    for (const field of props.source.fields) {
        if (field.type === 'email-list' || field.type === 'string-list') {
            const sep = field.separator ?? ',';
            result[field.name] = String(fieldValues[field.name] ?? '')
                .split(sep)
                .map((s) => s.trim())
                .filter(Boolean);
        } else {
            result[field.name] = fieldValues[field.name];
        }
    }

    return { [props.source.value]: result };
}

function save(visitOptions: { preserveScroll?: boolean; onSuccess?: () => void } = {}): void {
    const { onSuccess, ...rest } = visitOptions;

    form.transform(() => buildPayload()).submit(sourcesRoutes.update(), {
        ...rest,
        onSuccess,
        onError: (serverErrors: Record<string, string>) => {
            if (serverErrors[`${props.source.value}.enabled`]) {
                form.enabled = Boolean(props.source.config.enabled);
                showForm.value = Boolean(props.source.config.enabled);
            }
        },
    });
}

// ── Toggle ──────────────────────────────────────────────────────────────────

function onToggle(val: boolean): void {
    form.enabled = val;

    if (!val) {
        showForm.value = false;
    }

    save({
        preserveScroll: true,
        onSuccess: () => {
            if (val) {
                showForm.value = true;
            }
        },
    });
}

// ── Test ────────────────────────────────────────────────────────────────────

type TestStatus = 'idle' | 'loading' | 'ok' | 'fail';
const testStatus = ref<TestStatus>('idle');

const verifyVariant = computed(() => {
    if (testStatus.value === 'fail') return 'destructive' as const;
    return 'outline' as const;
});

const verifyClass = computed(() => {
    if (testStatus.value === 'ok') return '!border-green-500 !bg-green-500 !text-white hover:!bg-green-600';
    return '';
});

function getCsrfToken(): string {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function handleTest(): Promise<void> {
    testStatus.value = 'loading';
    try {
        const response = await fetch(sourcesRoutes.test(props.source.value).url, {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        testStatus.value = data.available ? 'ok' : 'fail';
    } catch {
        testStatus.value = 'fail';
    }
}

// ── Field helpers ────────────────────────────────────────────────────────────

function fieldValue(field: SourceFieldDefinition): unknown {
    return fieldValues[field.name];
}

function setFieldValue(field: SourceFieldDefinition, value: unknown): void {
    fieldValues[field.name] = value;
}
</script>

<template>
    <Card class="gap-0 overflow-hidden py-0">
        <!-- Header: title + description + toggle -->
        <CardHeader class="border-b py-4">
            <CardTitle class="text-base font-semibold">{{ source.label }}</CardTitle>
            <CardDescription>{{ source.description }}</CardDescription>
            <CardAction class="self-center">
                <Loader2 v-if="form.processing" class="size-4 animate-spin text-muted-foreground" />
                <Switch v-else v-model="form.enabled" @update:model-value="onToggle" />
            </CardAction>
        </CardHeader>

        <!-- Availability error -->
        <div
            v-if="errors[`${source.value}.enabled`]"
            class="border-b bg-destructive/5 px-6 py-3 text-sm text-destructive [&_code]:rounded [&_code]:border [&_code]:border-destructive [&_code]:bg-destructive [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[11px] [&_code]:text-white"
            v-html="errors[`${source.value}.enabled`]"
        ></div>

        <!-- Config content: visible once enabled save succeeds (or was already enabled) -->
        <CardContent v-if="showForm" class="py-5">
            <form class="flex flex-col gap-4" @submit.prevent="save()">
                <template v-for="field in source.fields" :key="field.name">
                    <InputField :label="field.label" :error="errors[field.name]" :hint="field.hint">
                        <Input
                            v-if="field.type === 'text' || field.type === 'email-list'"
                            :model-value="String(fieldValue(field) ?? '')"
                            type="text"
                            :placeholder="field.placeholder ?? ''"
                            @update:model-value="(v) => setFieldValue(field, v)"
                        />
                        <Input
                            v-else-if="field.type === 'number'"
                            :model-value="Number(fieldValue(field) ?? 0)"
                            type="number"
                            :min="field.min ?? undefined"
                            :max="field.max ?? undefined"
                            class="max-w-32"
                            @update:model-value="(v) => setFieldValue(field, v === '' ? 0 : Number(v))"
                        />
                        <Textarea
                            v-else-if="field.type === 'textarea' || field.type === 'string-list'"
                            :model-value="String(fieldValue(field) ?? '')"
                            :rows="field.rows ?? 4"
                            class="font-mono text-xs"
                            @update:model-value="(v) => setFieldValue(field, v)"
                        />
                    </InputField>
                </template>
                <div>
                    <Button type="submit" size="sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </div>
            </form>
        </CardContent>

        <!-- Footer: requirements + verify button -->
        <CardFooter class="flex items-center justify-between gap-4 bg-muted/40 px-6 py-3">
            <p
                class="text-xs text-muted-foreground [&_code]:rounded [&_code]:border [&_code]:bg-background [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[11px]"
                v-html="source.requirements"
            />

            <Button :variant="verifyVariant" :class="verifyClass" size="sm" :disabled="testStatus === 'loading'" @click="handleTest">
                Verify
                <Loader2 v-if="testStatus === 'loading'" class="size-4 animate-spin" />
                <Check v-else-if="testStatus === 'ok'" class="size-4" />
                <XIcon v-else-if="testStatus === 'fail'" class="size-4" />
            </Button>
        </CardFooter>
    </Card>
</template>
