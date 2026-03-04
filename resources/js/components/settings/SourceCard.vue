<script setup lang="ts">
import { Check, Loader2, XIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import * as sourcesRoutes from '@/routes/settings/sources';

const props = defineProps<{
    title: string;
    description: string;
    requirements: string;
    sourceValue: string;
    enabled: boolean;
}>();

const emit = defineEmits<{
    'update:enabled': [boolean];
}>();

// Local ref so the toggle responds immediately without waiting for the server round-trip
const localEnabled = ref(props.enabled);
watch(
    () => props.enabled,
    (val) => {
        localEnabled.value = val;
    },
);

function onToggle(val: boolean): void {
    localEnabled.value = val;
    emit('update:enabled', val);
}

// ── Test ───────────────────────────────────────────────────────────────────

type TestStatus = 'idle' | 'loading' | 'ok' | 'fail';
const testStatus = ref<TestStatus>('idle');

const verifyVariant = computed(() => {
    if (testStatus.value === 'fail') return 'destructive' as const;
    return 'outline' as const;
});

const verifyClass = computed(() => {
    if (testStatus.value === 'ok') return 'border-green-500 bg-green-500 text-white hover:bg-green-600 hover:text-white';
    return '';
});

function getCsrfToken(): string {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function handleTest(): Promise<void> {
    testStatus.value = 'loading';
    try {
        const response = await fetch(sourcesRoutes.test(props.sourceValue).url, {
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
</script>

<template>
    <Card class="gap-0 overflow-hidden py-0">
        <!-- Header: title + description + toggle -->
        <CardHeader class="border-b py-4">
            <CardTitle class="text-base font-semibold">{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
            <CardAction class="self-center">
                <Switch :model-value="localEnabled" @update:model-value="onToggle" />
            </CardAction>
        </CardHeader>

        <!-- Config content: only when enabled -->
        <CardContent v-if="localEnabled" class="py-5">
            <slot />
        </CardContent>

        <!-- Footer: requirements + verify button (no top border — bg separation is enough) -->
        <CardFooter class="bg-muted/40 flex items-center justify-between gap-4 px-6 py-3">
            <p
                class="text-muted-foreground text-xs [&_code]:bg-background [&_code]:rounded [&_code]:border [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[11px]"
                v-html="requirements"
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
