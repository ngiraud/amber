<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Loader2, XIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { SourceDefinition, SourceFieldDefinition } from '@/types';
import { cn } from '@/lib/utils';

const props = defineProps<{
    source: SourceDefinition;
    open?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

// ── Form ────────────────────────────────────────────────────────────────────

const form = useForm({ enabled: Boolean(props.source.config.enabled) });
const internalOpen = ref(false);

// Use external open prop if provided, otherwise use internal state
const isOpen = computed({
    get: () => (props.open !== undefined ? props.open : internalOpen.value),
    set: (val) => {
        if (props.open !== undefined) {
            emit('update:open', val);
        } else {
            internalOpen.value = val;
        }
    },
});

// Field values as a plain reactive record
const fieldValues = reactive<Record<string, unknown>>(initFieldValues());

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

function save(): void {
    form.transform(() => buildPayload()).submit(sourcesRoutes.update(props.source.value), {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
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

// Field helpers
function fieldValue(field: SourceFieldDefinition): unknown {
    return fieldValues[field.name];
}

function setFieldValue(field: SourceFieldDefinition, value: unknown): void {
    fieldValues[field.name] = value;
}

// Sync enabled state
watch(
    () => props.source.config.enabled,
    (val) => {
        form.enabled = Boolean(val);
    },
);
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ source.label }} Configuration</SheetTitle>
                <SheetDescription> Configure the settings for this activity source. </SheetDescription>
            </SheetHeader>

            <form class="flex flex-col gap-6 overflow-y-auto px-4 py-6" @submit.prevent="save">
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

                <div class="mt-2 flex flex-col gap-3">
                    <Button type="submit" :disabled="form.processing" class="w-full">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <Button
                        type="button"
                        :variant="verifyVariant"
                        :class="cn('w-full', verifyClass)"
                        :disabled="testStatus === 'loading'"
                        @click="handleTest"
                    >
                        <Loader2 v-if="testStatus === 'loading'" class="mr-2 size-4 animate-spin" />
                        <Check v-else-if="testStatus === 'ok'" class="mr-2 size-4" />
                        <XIcon v-else-if="testStatus === 'fail'" class="mr-2 size-4" />
                        Verify Connection
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>
