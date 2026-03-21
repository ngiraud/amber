<script setup lang="ts">
import { Form, useForm } from '@inertiajs/vue3';
import { Check, Loader2, XIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Switch } from '@/components/ui/switch';
import { useSpotlight } from '@/composables/useSpotlight';
import { t } from '@/composables/useTranslation';
import * as aiRoutes from '@/routes/settings/ai';
import type { AiProviderOption, AiSettings } from '@/types';

const { spotlightClass } = useSpotlight();

const props = defineProps<{
    aiSettings: AiSettings;
    providers: AiProviderOption[];
}>();

const languageOptions = computed(() => [
    { value: 'fr', label: t('app.locales.fr') },
    { value: 'en', label: t('app.locales.en') },
]);

const form = useForm({
    enabled: props.aiSettings.enabled,
    provider: props.aiSettings.provider ?? '',
    api_key: props.aiSettings.api_key ?? '',
    summary_language: props.aiSettings.summary_language,
});

const selectedProvider = computed(() => props.providers.find((p) => p.value === form.provider) ?? null);

// ── Test connection ──────────────────────────────────────────────────────────

type TestStatus = 'idle' | 'loading' | 'ok' | 'fail';
const testStatus = ref<TestStatus>('idle');

const verifyVariant = computed(() => {
    if (testStatus.value === 'fail') {
        return 'destructive' as const;
    }

    return 'outline' as const;
});

const verifyClass = computed(() => {
    if (testStatus.value === 'ok') {
        return '!border-green-500 !bg-green-500 !text-white hover:!bg-green-600';
    }

    return '';
});

function getCsrfToken(): string {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function handleTest(): Promise<void> {
    testStatus.value = 'loading';

    try {
        const response = await fetch(aiRoutes.test().url, {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        testStatus.value = data.success ? 'ok' : 'fail';
    } catch {
        testStatus.value = 'fail';
    }
}
</script>

<template>
    <SettingsLayout active-tab="ai">
        <Form @submit.prevent="form.submit(aiRoutes.update())">
            <Card :class="['gap-0 overflow-hidden py-0', spotlightClass('ai')]">
                <!-- Header: title + description + toggle -->
                <CardHeader class="border-b py-4">
                    <CardTitle class="text-base font-semibold">{{ t('app.settings.ai_section.title') }}</CardTitle>
                    <CardDescription>{{ t('app.settings.ai_section.description') }}</CardDescription>
                    <CardAction class="self-center">
                        <Switch v-model="form.enabled" />
                    </CardAction>
                </CardHeader>

                <!-- Config content: only when enabled -->
                <CardContent v-if="form.enabled" class="flex flex-col gap-4 py-5">
                    <InputField
                        :label="t('app.settings.ai_section.provider')"
                        :error="form.errors.provider"
                        :hint="selectedProvider?.model ? t('app.settings.ai_section.cheapest_model', { model: selectedProvider.model }) : undefined"
                    >
                        <NativeSelect v-model="form.provider" class="w-full">
                            <NativeSelectOption value="" disabled>{{ t('app.settings.ai_section.select_provider') }}</NativeSelectOption>
                            <NativeSelectOption v-for="provider in providers" :key="provider.value" :value="provider.value">
                                {{ provider.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                    </InputField>

                    <InputField
                        :label="t('app.settings.ai_section.api_key')"
                        :error="form.errors.api_key"
                        :hint="
                            selectedProvider?.requiresApiKey
                                ? t('app.settings.ai_section.api_key_hint_secure')
                                : t('app.settings.ai_section.api_key_hint_not_required')
                        "
                    >
                        <Input
                            v-show="selectedProvider?.requiresApiKey"
                            v-model="form.api_key"
                            type="password"
                            :placeholder="t('app.settings.ai_section.api_key_placeholder')"
                            :disabled="!selectedProvider?.requiresApiKey"
                        />
                    </InputField>

                    <InputField :label="t('app.settings.ai_section.summary_language')" :error="form.errors.summary_language">
                        <NativeSelect v-model="form.summary_language" class="w-full">
                            <NativeSelectOption v-for="lang in languageOptions" :key="lang.value" :value="lang.value">
                                {{ lang.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                    </InputField>
                </CardContent>

                <!-- Footer: info + verify -->
                <CardFooter class="flex items-center justify-between gap-4 bg-muted/40 px-6 py-3">
                    <p class="text-xs text-muted-foreground">{{ t('app.settings.ai_section.footer_note') }}</p>

                    <div class="flex items-center justify-end gap-4">
                        <Button
                            v-if="aiSettings.enabled"
                            type="button"
                            :variant="verifyVariant"
                            :class="verifyClass"
                            size="sm"
                            :disabled="testStatus === 'loading'"
                            @click="handleTest"
                        >
                            {{ t('app.settings.ai_section.test_connection') }}
                            <Loader2 v-if="testStatus === 'loading'" class="size-4 animate-spin" />
                            <Check v-else-if="testStatus === 'ok'" class="size-4" />
                            <XIcon v-else-if="testStatus === 'fail'" class="size-4" />
                        </Button>

                        <Button type="submit" size="sm" :disabled="form.processing">
                            {{ form.processing ? t('app.common.saving') : t('app.common.save') }}
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </Form>
    </SettingsLayout>
</template>
