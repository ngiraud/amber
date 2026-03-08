<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import TimezoneCombobox from '@/components/TimezoneCombobox.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
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
    default_hourly_rate: props.generalSettings.default_hourly_rate ?? undefined,
    default_daily_rate: props.generalSettings.default_daily_rate ?? undefined,
    default_daily_reference_hours: props.generalSettings.default_daily_reference_hours ?? 7,
    default_rounding_strategy: props.generalSettings.default_rounding_strategy ?? 15,
    timezone: props.generalSettings.timezone ?? '',
    locale: props.generalSettings.locale ?? '',
    theme: props.generalSettings.theme ?? 'system',
    open_at_login: props.generalSettings.open_at_login ?? false,
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

                <CardContent class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4">
                        <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Preferences</h2>

                        <div class="grid grid-cols-2 gap-4">
                            <InputField label="Timezone" :error="form.errors.timezone">
                                <TimezoneCombobox v-model="form.timezone" :timezones="timezones" />
                            </InputField>

                            <InputField label="Language" :error="form.errors.locale">
                                <NativeSelect v-model="form.locale" class="w-full">
                                    <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                        {{ locale.label }}
                                    </option>
                                </NativeSelect>
                            </InputField>
                        </div>

                        <InputField label="Theme" :error="form.errors.theme">
                            <AppearanceTabs v-model="form.theme" />
                        </InputField>

                        <InputField label="Launch at login" description="Automatically open Activity Record when you log in" direction="horizontal">
                            <Switch v-model="form.open_at_login" />
                        </InputField>
                    </div>

                    <Separator />

                    <div class="flex flex-col gap-4">
                        <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Company</h2>

                        <InputField label="Company name" :error="form.errors.company_name">
                            <Input v-model="form.company_name" type="text" />
                        </InputField>

                        <InputField label="Company address" :error="form.errors.company_address">
                            <Textarea v-model="form.company_address" rows="2" />
                        </InputField>
                    </div>

                    <Separator />

                    <div class="flex flex-col gap-4">
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
                                <NativeSelect v-model.number="form.default_rounding_strategy" class="w-full">
                                    <option v-for="option in ROUNDING_OPTIONS" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </NativeSelect>
                            </InputField>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </form>
    </SettingsLayout>
</template>
