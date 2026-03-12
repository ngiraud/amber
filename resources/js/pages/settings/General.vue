<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import TimezoneCombobox from '@/components/TimezoneCombobox.vue';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Item, ItemActions, ItemContent, ItemDescription, ItemTitle } from '@/components/ui/item';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useSpotlight } from '@/composables/useSpotlight';
import * as settingsRoutes from '@/routes/settings';
import * as generalRoutes from '@/routes/settings/general';
import type { GeneralSettings } from '@/types';

const { spotlightClass } = useSpotlight();

const resetConfirmInput = ref('');
const isResetOpen = ref(false);

function confirmReset(): void {
    router.post(settingsRoutes.reset());
}

const props = defineProps<{
    generalSettings: GeneralSettings;
    timezones: string[];
    // locales: LocaleOption[];
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
    // locale: props.generalSettings.locale ?? '',
    theme: props.generalSettings.theme ?? 'system',
    open_at_login: props.generalSettings.open_at_login ?? false,
});

function submit(): void {
    form.submit(generalRoutes.update());
}
</script>

<template>
    <SettingsLayout active-tab="general">
        <div class="flex flex-col gap-4">
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

                            <InputField label="Timezone" :error="form.errors.timezone">
                                <TimezoneCombobox v-model="form.timezone" :timezones="timezones" />
                            </InputField>

                            <InputField label="Theme" :error="form.errors.theme" direction="horizontal">
                                <AppearanceTabs v-model="form.theme" />
                            </InputField>

                            <InputField
                                label="Launch at login"
                                description="Automatically open Activity Record when you log in"
                                direction="horizontal"
                            >
                                <Switch v-model="form.open_at_login" />
                            </InputField>
                        </div>

                        <Separator />

                        <div :class="['flex flex-col gap-4 rounded-lg p-0 transition-all', spotlightClass('company')]">
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
                                        <NativeSelectOption v-for="option in ROUNDING_OPTIONS" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </NativeSelectOption>
                                    </NativeSelect>
                                </InputField>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <Card class="border-destructive/40 shadow-destructive">
                <CardHeader>
                    <CardTitle class="text-destructive">Danger Zone</CardTitle>
                    <CardDescription>Irreversible actions that permanently affect your data</CardDescription>
                </CardHeader>

                <CardContent>
                    <div class="flex flex-col gap-4">
                        <Item variant="muted">
                            <ItemContent>
                                <ItemTitle>Reset all data</ItemTitle>
                                <ItemDescription>Permanently delete all activity records, sessions, clients, projects, and settings.</ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <AlertDialog v-model="isResetOpen">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="destructive" size="sm" @click="resetConfirmInput = ''">Reset all data</Button>
                                    </AlertDialogTrigger>

                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Reset all data?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will permanently delete everything: activity records, sessions, clients, projects, reports, and
                                                all settings. The database will be wiped and reset to a blank state.
                                                <strong class="text-foreground">This action cannot be undone.</strong>
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>

                                        <div class="flex flex-col gap-2 py-2">
                                            <label class="text-sm font-medium">
                                                Type <span class="font-mono font-bold">RESET</span> to confirm
                                            </label>
                                            <Input v-model="resetConfirmInput" placeholder="RESET" />
                                        </div>

                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <Button variant="destructive" :disabled="resetConfirmInput !== 'RESET'" @click="confirmReset">
                                                Reset all data
                                            </Button>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </ItemActions>
                        </Item>
                    </div>
                </CardContent>
            </Card>
        </div>
    </SettingsLayout>
</template>
