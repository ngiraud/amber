<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import * as generalRoutes from '@/routes/settings/general';
import type { GeneralSettings, LocaleOption } from '@/types';

const props = defineProps<{
    generalSettings: GeneralSettings;
    timezones: string[];
    locales: LocaleOption[];
}>();

const ROUNDING_OPTIONS = [
    { value: 15, label: 'Quarter hour (15 min)' },
    { value: 30, label: 'Half hour (30 min)' },
    { value: 60, label: 'Hour (60 min)' },
];

const form = useForm({
    company_name: props.generalSettings.company_name ?? '',
    company_address: props.generalSettings.company_address ?? '',
    default_hourly_rate: props.generalSettings.default_hourly_rate ?? null,
    default_daily_rate: props.generalSettings.default_daily_rate ?? null,
    default_daily_reference_hours: props.generalSettings.default_daily_reference_hours ?? 7,
    default_rounding_strategy: props.generalSettings.default_rounding_strategy ?? 15,
    timezone: props.generalSettings.timezone ?? '',
    locale: props.generalSettings.locale ?? '',
});

function submit(): void {
    form.submit(generalRoutes.update());
}
</script>

<template>
    <SettingsLayout active-tab="general">
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>General</CardTitle>
                    <CardDescription>Company info, billing defaults, and regional preferences</CardDescription>
                    <CardAction>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="flex flex-col gap-4 pt-6">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Preferences</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Timezone" :error="form.errors.timezone">
                            <select
                                v-model="form.timezone"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">— system default —</option>
                                <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </InputField>

                        <InputField label="Language" :error="form.errors.locale">
                            <select
                                v-model="form.locale"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">— system default —</option>
                                <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                    {{ locale.label }}
                                </option>
                            </select>
                        </InputField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Theme" :error="form.errors.theme">
                            <AppearanceTabs />
                        </InputField>
                    </div>

                    <Separator />

                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Company</h2>

                    <InputField label="Company name" :error="form.errors.company_name">
                        <Input v-model="form.company_name" type="text" />
                    </InputField>

                    <InputField label="Company address" :error="form.errors.company_address">
                        <Textarea v-model="form.company_address" rows="2" />
                    </InputField>

                    <Separator />

                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Billing defaults</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Hourly rate (€)" :error="form.errors.default_hourly_rate">
                            <Input v-model.number="form.default_hourly_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                        </InputField>

                        <InputField label="Daily rate (€)" :error="form.errors.default_daily_rate">
                            <Input v-model.number="form.default_daily_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                        </InputField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Daily reference hours" :error="form.errors.default_daily_reference_hours">
                            <Input v-model.number="form.default_daily_reference_hours" type="number" min="1" max="24" />
                        </InputField>

                        <InputField label="Default rounding" :error="form.errors.default_rounding_strategy">
                            <select
                                v-model.number="form.default_rounding_strategy"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option v-for="option in ROUNDING_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </InputField>
                    </div>
                </CardContent>
            </Card>
        </form>
    </SettingsLayout>
</template>
