<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputField from '@/components/InputField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as settingsRoutes from '@/routes/settings';
import type { AppSettings } from '@/types';

const props = defineProps<{
    settings: AppSettings;
}>();

const ROUNDING_OPTIONS = [
    { value: 15, label: '15 min' },
    { value: 30, label: '30 min' },
    { value: 60, label: '1 hour' },
];

function transformSettings(data: Record<string, unknown>): Record<string, unknown> {
    const emails = typeof data.git_author_emails === 'string' ? data.git_author_emails : '';

    return {
        ...data,
        git_author_emails: emails
            ? emails
                  .split(',')
                  .map((e: string) => e.trim())
                  .filter(Boolean)
            : [],
    };
}
</script>

<template>
    <AppLayout title="Settings">
        <div class="max-w-lg">
            <h1 class="text-xl font-semibold text-gray-900">Settings</h1>

            <Form
                class="mt-6 flex flex-col gap-8"
                :action="settingsRoutes.update()"
                :transform="transformSettings"
                #default="{ errors, processing }"
            >
                <!-- Git -->
                <section class="flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Git</h2>

                    <InputField
                        label="Author emails"
                        :error="errors.git_author_emails"
                        hint="Comma-separated list of emails used in git commits"
                    >
                        <input
                            name="git_author_emails"
                            type="text"
                            :defaultValue="settings.git_author_emails?.join(', ')"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            placeholder="me@example.com, work@example.com"
                        />
                    </InputField>
                </section>

                <!-- Company -->
                <section class="flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Company</h2>

                    <InputField label="Company name" :error="errors.company_name">
                        <input
                            name="company_name"
                            type="text"
                            :defaultValue="settings.company_name ?? undefined"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        />
                    </InputField>

                    <InputField label="Company address" :error="errors.company_address">
                        <textarea
                            name="company_address"
                            rows="2"
                            :defaultValue="settings.company_address ?? undefined"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        />
                    </InputField>
                </section>

                <!-- Defaults -->
                <section class="flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Defaults</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Hourly rate (€)" :error="errors.default_hourly_rate">
                            <input
                                name="default_hourly_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                :defaultValue="settings.default_hourly_rate ?? undefined"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                                placeholder="0.00"
                            />
                        </InputField>

                        <InputField label="Daily rate (€)" :error="errors.default_daily_rate">
                            <input
                                name="default_daily_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                :defaultValue="settings.default_daily_rate ?? undefined"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                                placeholder="0.00"
                            />
                        </InputField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Daily reference hours" :error="errors.default_daily_reference_hours">
                            <input
                                name="default_daily_reference_hours"
                                type="number"
                                min="1"
                                max="24"
                                :defaultValue="settings.default_daily_reference_hours ?? 7"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            />
                        </InputField>

                        <InputField label="Rounding" :error="errors.default_rounding_strategy">
                            <select
                                name="default_rounding_strategy"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            >
                                <option
                                    v-for="option in ROUNDING_OPTIONS"
                                    :key="option.value"
                                    :value="option.value"
                                    :selected="option.value === (settings.default_rounding_strategy ?? 15)"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </InputField>
                    </div>
                </section>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Saving…' : 'Save settings' }}
                    </button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
