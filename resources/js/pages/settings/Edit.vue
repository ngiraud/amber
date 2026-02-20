<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import * as settingsRoutes from '@/routes/settings';
import type { AppSettings } from '@/types';

defineProps<{
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
            <h1 class="text-xl font-semibold">Settings</h1>

            <Form class="mt-6 flex flex-col gap-8" :action="settingsRoutes.update()" :transform="transformSettings" #default="{ errors, processing }">
                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Git</h2>

                    <InputField label="Author emails" :error="errors.git_author_emails" hint="Comma-separated list of emails used in git commits">
                        <Input
                            name="git_author_emails"
                            type="text"
                            :default-value="settings.git_author_emails?.join(', ')"
                            placeholder="me@example.com, work@example.com"
                        />
                    </InputField>
                </section>

                <Separator />

                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Company</h2>

                    <InputField label="Company name" :error="errors.company_name">
                        <Input name="company_name" type="text" :default-value="settings.company_name ?? undefined" />
                    </InputField>

                    <InputField label="Company address" :error="errors.company_address">
                        <Textarea name="company_address" rows="2" :default-value="settings.company_address ?? undefined" />
                    </InputField>
                </section>

                <Separator />

                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Defaults</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Hourly rate (€)" :error="errors.default_hourly_rate">
                            <Input
                                name="default_hourly_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                :default-value="settings.default_hourly_rate ?? undefined"
                                placeholder="0.00"
                            />
                        </InputField>

                        <InputField label="Daily rate (€)" :error="errors.default_daily_rate">
                            <Input
                                name="default_daily_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                :default-value="settings.default_daily_rate ?? undefined"
                                placeholder="0.00"
                            />
                        </InputField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Daily reference hours" :error="errors.default_daily_reference_hours">
                            <Input
                                name="default_daily_reference_hours"
                                type="number"
                                min="1"
                                max="24"
                                :default-value="settings.default_daily_reference_hours ?? 7"
                            />
                        </InputField>

                        <InputField label="Rounding" :error="errors.default_rounding_strategy">
                            <select
                                name="default_rounding_strategy"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save settings' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
