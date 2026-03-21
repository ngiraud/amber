<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUpCircleIcon, CheckCircle2Icon, LoaderCircleIcon, RotateCcwIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import { applyTheme } from '@/composables/useAppearance';
import { useDateFormat } from '@/composables/useDateFormat';
import { useSpotlight } from '@/composables/useSpotlight';
import { setLocale, t } from '@/composables/useTranslation';
import {
    checkForUpdates,
    checkGitHubRelease,
    downloadProgress,
    githubReleaseInfo,
    githubReleaseStatus,
    installUpdate,
    updateInfo,
    updaterStatus,
} from '@/composables/useUpdater';
import * as settingsRoutes from '@/routes/settings';
import * as generalRoutes from '@/routes/settings/general';
import type { GeneralSettings } from '@/types';

const { spotlightClass } = useSpotlight();
const { formatDate } = useDateFormat();

const resetConfirmInput = ref('');
const isResetOpen = ref(false);

function confirmReset(): void {
    router.post(settingsRoutes.reset());
}

const props = defineProps<{
    generalSettings: GeneralSettings;
    timezones: string[];
    dateFormats: { value: string; label: string }[];
    timeFormats: { value: string; label: string }[];
    locales: { value: string; label: string }[];
}>();

const page = usePage();
const appVersion = computed(() => page.props.appVersion);
const updaterEnabled = computed(() => page.props.updaterEnabled);
const appName = computed(() => page.props.name);

const roundingOptions = computed(() => [
    { value: 15, label: t('app.settings.rounding.quarter_hour') },
    { value: 30, label: t('app.settings.rounding.half_hour') },
    { value: 60, label: t('app.settings.rounding.hour') },
]);

const form = useForm({
    company_name: props.generalSettings.company_name ?? '',
    company_address: props.generalSettings.company_address ?? '',
    default_hourly_rate: props.generalSettings.default_hourly_rate ?? undefined,
    default_daily_rate: props.generalSettings.default_daily_rate ?? undefined,
    default_daily_reference_hours: props.generalSettings.default_daily_reference_hours ?? 8,
    default_rounding_strategy: props.generalSettings.default_rounding_strategy ?? 15,
    timezone: props.generalSettings.timezone ?? '',
    locale: props.generalSettings.locale ?? 'en',
    date_format: props.generalSettings.date_format ?? 'd/m/Y',
    time_format: props.generalSettings.time_format ?? 'H:i',
    theme: props.generalSettings.theme ?? 'system',
    open_at_login: props.generalSettings.open_at_login ?? false,
});

watch(
    () => form.theme,
    (theme) => applyTheme(theme),
);

const localeChanged = computed(() => form.locale !== props.generalSettings.locale);

function submit(): void {
    const newLocale = form.locale;
    const shouldUpdateLocale = localeChanged.value;

    form.submit(generalRoutes.update(), {
        onSuccess: () => {
            if (shouldUpdateLocale) {
                setLocale(newLocale);
            }
        },
    });
}
</script>

<template>
    <SettingsLayout active-tab="general">
        <div class="flex flex-col gap-4">
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('app.settings.general') }}</CardTitle>
                        <CardDescription>{{ t('app.settings.general_description') }}</CardDescription>
                        <CardAction>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? t('app.common.saving') : t('app.common.save') }}
                            </Button>
                        </CardAction>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-4">
                        <div class="flex flex-col gap-4">
                            <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                {{ t('app.settings.sections.preferences') }}
                            </h2>

                            <InputField :label="t('app.settings.fields.language')" :error="form.errors.locale" direction="horizontal">
                                <NativeSelect v-model="form.locale" class="w-48">
                                    <NativeSelectOption v-for="option in locales" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </NativeSelectOption>
                                </NativeSelect>
                            </InputField>

                            <InputField :label="t('app.settings.fields.timezone')" :error="form.errors.timezone" direction="horizontal">
                                <div class="w-48">
                                    <TimezoneCombobox v-model="form.timezone" :timezones="timezones" class="w-48" />
                                </div>
                            </InputField>

                            <InputField :label="t('app.settings.fields.date_format')" :error="form.errors.date_format" direction="horizontal">
                                <NativeSelect v-model="form.date_format" class="w-48">
                                    <NativeSelectOption v-for="option in dateFormats" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </NativeSelectOption>
                                </NativeSelect>
                            </InputField>

                            <InputField :label="t('app.settings.fields.time_format')" :error="form.errors.time_format" direction="horizontal">
                                <NativeSelect v-model="form.time_format" class="w-48">
                                    <NativeSelectOption v-for="option in timeFormats" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </NativeSelectOption>
                                </NativeSelect>
                            </InputField>

                            <InputField :label="t('app.settings.fields.theme')" :error="form.errors.theme" direction="horizontal">
                                <AppearanceTabs v-model="form.theme" />
                            </InputField>

                            <InputField
                                :label="t('app.settings.fields.open_at_login')"
                                :description="t('app.settings.fields.open_at_login_description', { app: appName })"
                                direction="horizontal"
                            >
                                <Switch v-model="form.open_at_login" />
                            </InputField>
                        </div>

                        <Separator />

                        <div :class="['flex flex-col gap-4 rounded-lg p-0 transition-all', spotlightClass('company')]">
                            <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                {{ t('app.settings.sections.company') }}
                            </h2>

                            <InputField :label="t('app.settings.fields.company_name')" :error="form.errors.company_name">
                                <Input v-model="form.company_name" type="text" />
                            </InputField>

                            <InputField :label="t('app.settings.fields.company_address')" :error="form.errors.company_address">
                                <Textarea v-model="form.company_address" rows="2" />
                            </InputField>
                        </div>

                        <Separator />

                        <div class="flex flex-col gap-4">
                            <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                {{ t('app.settings.sections.billing_defaults') }}
                            </h2>

                            <div class="grid grid-cols-2 gap-4">
                                <InputField :label="t('app.settings.fields.hourly_rate')" :error="form.errors.default_hourly_rate">
                                    <Input v-model.number="form.default_hourly_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                                </InputField>

                                <InputField :label="t('app.settings.fields.daily_rate')" :error="form.errors.default_daily_rate">
                                    <Input v-model.number="form.default_daily_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                                </InputField>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <InputField
                                    :label="t('app.settings.fields.daily_reference_hours')"
                                    :error="form.errors.default_daily_reference_hours"
                                >
                                    <Input v-model.number="form.default_daily_reference_hours" type="number" min="1" max="24" />
                                </InputField>

                                <InputField :label="t('app.settings.fields.default_rounding')" :error="form.errors.default_rounding_strategy">
                                    <NativeSelect v-model.number="form.default_rounding_strategy" class="w-full">
                                        <NativeSelectOption v-for="option in roundingOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </NativeSelectOption>
                                    </NativeSelect>
                                </InputField>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <!-- Software Updates: auto-updater enabled -->
            <Card v-if="updaterEnabled">
                <CardHeader>
                    <CardTitle>{{ t('app.settings.sections.software_updates') }}</CardTitle>
                    <CardDescription>{{ t('app.settings.updates.current_version', { version: appVersion }) }}</CardDescription>
                    <CardAction>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="!['idle', 'up-to-date', 'error'].includes(updaterStatus)"
                            @click="checkForUpdates"
                        >
                            <LoaderCircleIcon v-if="updaterStatus === 'checking'" class="size-3.5 animate-spin" />
                            <ArrowUpCircleIcon v-else class="size-3.5" />
                            {{ updaterStatus === 'checking' ? t('app.settings.updates.checking') : t('app.settings.updates.check') }}
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <p v-if="updaterStatus === 'idle' || updaterStatus === 'checking'" class="text-sm text-muted-foreground">
                        {{ updaterStatus === 'checking' ? t('app.settings.updates.checking_for_updates') : t('app.settings.updates.up_to_date') }}
                    </p>

                    <div v-else-if="updaterStatus === 'up-to-date'" class="flex items-center gap-2 text-sm text-muted-foreground">
                        <CheckCircle2Icon class="size-4 text-green-500" />
                        {{ t('app.settings.updates.up_to_date') }}
                    </div>

                    <p v-else-if="updaterStatus === 'error'" class="text-sm text-destructive">{{ t('app.settings.updates.error') }}</p>

                    <div v-else-if="updateInfo" class="flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-col gap-0.5">
                                <p class="text-sm font-medium">{{ t('app.settings.updates.available', { version: updateInfo.version }) }}</p>
                                <p v-if="updateInfo.releaseDate" class="text-xs text-muted-foreground">
                                    {{ t('app.settings.updates.released', { date: formatDate(updateInfo.releaseDate) }) }}
                                </p>
                            </div>

                            <Button v-if="updaterStatus === 'ready'" size="sm" @click="installUpdate">
                                <RotateCcwIcon class="size-3.5" />
                                {{ t('app.settings.updates.restart_install') }}
                            </Button>
                        </div>

                        <div v-if="updaterStatus === 'downloading'" class="flex flex-col gap-1.5">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary transition-all duration-300" :style="{ width: `${downloadProgress}%` }" />
                            </div>
                            <p class="text-xs text-muted-foreground">{{ t('app.settings.updates.downloading', { percent: downloadProgress }) }}</p>
                        </div>

                        <p
                            v-if="updateInfo.releaseNotes"
                            class="max-h-40 overflow-y-auto rounded-md bg-muted px-3 py-2 text-xs whitespace-pre-line text-muted-foreground"
                        >
                            {{ Array.isArray(updateInfo.releaseNotes) ? updateInfo.releaseNotes.join('\n') : updateInfo.releaseNotes }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Software Updates: manual GitHub check (updater disabled) -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>{{ t('app.settings.sections.software_updates') }}</CardTitle>
                    <CardDescription>{{ t('app.settings.updates.current_version', { version: appVersion }) }}</CardDescription>
                    <CardAction>
                        <Button size="sm" variant="outline" :disabled="githubReleaseStatus === 'checking'" @click="checkGitHubRelease(appVersion)">
                            <LoaderCircleIcon v-if="githubReleaseStatus === 'checking'" class="size-3.5 animate-spin" />
                            <ArrowUpCircleIcon v-else class="size-3.5" />
                            {{ githubReleaseStatus === 'checking' ? t('app.settings.updates.checking') : t('app.settings.updates.check') }}
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <p v-if="githubReleaseStatus === 'idle'" class="text-sm text-muted-foreground">
                        {{ t('app.settings.updates.auto_disabled') }}
                    </p>

                    <p v-else-if="githubReleaseStatus === 'checking'" class="text-sm text-muted-foreground">
                        {{ t('app.settings.updates.checking_for_updates') }}
                    </p>

                    <div v-else-if="githubReleaseStatus === 'up-to-date'" class="flex items-center gap-2 text-sm text-muted-foreground">
                        <CheckCircle2Icon class="size-4 text-green-500" />
                        {{ t('app.settings.updates.up_to_date') }}
                    </div>

                    <p v-else-if="githubReleaseStatus === 'error'" class="text-sm text-destructive">{{ t('app.settings.updates.error') }}</p>

                    <div v-else-if="githubReleaseStatus === 'available' && githubReleaseInfo" class="flex items-center justify-between gap-4">
                        <div class="flex flex-col gap-0.5">
                            <p class="text-sm font-medium">{{ t('app.settings.updates.available', { version: githubReleaseInfo.version }) }}</p>
                            <p v-if="githubReleaseInfo.publishedAt" class="text-xs text-muted-foreground">
                                {{ t('app.settings.updates.released', { date: formatDate(githubReleaseInfo.publishedAt) }) }}
                            </p>
                        </div>

                        <Button size="sm" variant="outline" as="a" :href="githubReleaseInfo.url" target="_blank">
                            <ArrowUpCircleIcon class="size-3.5" />
                            {{ t('app.settings.updates.view_release') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-destructive/40 shadow-destructive">
                <CardHeader>
                    <CardTitle class="text-destructive">{{ t('app.settings.sections.danger_zone') }}</CardTitle>
                    <CardDescription>{{ t('app.settings.reset.danger_description') }}</CardDescription>
                </CardHeader>

                <CardContent>
                    <div class="flex flex-col gap-4">
                        <Item variant="muted">
                            <ItemContent>
                                <ItemTitle>{{ t('app.settings.reset.item_title') }}</ItemTitle>
                                <ItemDescription>{{ t('app.settings.reset.item_description') }}</ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <AlertDialog v-model="isResetOpen">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="destructive" size="sm" @click="resetConfirmInput = ''">
                                            {{ t('app.settings.reset.button') }}
                                        </Button>
                                    </AlertDialogTrigger>

                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>{{ t('app.settings.reset.dialog_title') }}</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                {{ t('app.settings.reset.dialog_description') }}
                                                <strong class="text-foreground">{{ t('app.settings.reset.cannot_undo') }}</strong>
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>

                                        <div class="flex flex-col gap-2 py-2">
                                            <label class="text-sm font-medium">
                                                {{ t('app.settings.reset.type_to_confirm', { word: t('app.settings.reset.confirm_word') }) }}
                                            </label>
                                            <Input v-model="resetConfirmInput" :placeholder="t('app.settings.reset.confirm_placeholder')" />
                                        </div>

                                        <AlertDialogFooter>
                                            <AlertDialogCancel>{{ t('app.common.cancel') }}</AlertDialogCancel>
                                            <Button
                                                variant="destructive"
                                                :disabled="resetConfirmInput !== t('app.settings.reset.confirm_word')"
                                                @click="confirmReset"
                                            >
                                                {{ t('app.settings.reset.button') }}
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
