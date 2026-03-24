<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputField from '@/components/InputField.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { t } from '@/composables/useTranslation';
import * as activityRoutes from '@/routes/settings/activity';
import type { ActivitySettings } from '@/types';

const props = defineProps<{
    activitySettings: ActivitySettings;
}>();

const form = useForm({
    idle_timeout_minutes: props.activitySettings.idle_timeout_minutes ?? 30,
    block_end_padding_minutes: props.activitySettings.block_end_padding_minutes ?? 15,
    manual_session_reminder_minutes: props.activitySettings.manual_session_reminder_minutes ?? 60,
});

function submit(): void {
    form.submit(activityRoutes.update());
}
</script>

<template>
    <SettingsLayout active-tab="activity">
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('app.settings.activity') }}</CardTitle>
                    <CardDescription>{{ t('app.settings.activity_section.description') }}</CardDescription>
                    <CardAction>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? t('app.common.saving') : t('app.common.save') }}
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="flex flex-col gap-4 pt-0">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                        {{ t('app.settings.activity_section.timing') }}
                    </h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField
                            :label="t('app.settings.activity_section.idle_timeout_label')"
                            :error="form.errors.idle_timeout_minutes"
                            :hint="t('app.settings.activity_section.idle_timeout_hint')"
                        >
                            <Input v-model.number="form.idle_timeout_minutes" type="number" min="1" max="120" />
                        </InputField>

                        <InputField
                            :label="t('app.settings.activity_section.block_padding_label')"
                            :error="form.errors.block_end_padding_minutes"
                            :hint="t('app.settings.activity_section.block_padding_hint')"
                        >
                            <Input v-model.number="form.block_end_padding_minutes" type="number" min="0" max="60" />
                        </InputField>

                        <InputField
                            :label="t('app.settings.activity_section.reminder_label')"
                            :error="form.errors.manual_session_reminder_minutes"
                            :hint="t('app.settings.activity_section.reminder_hint')"
                        >
                            <Input v-model.number="form.manual_session_reminder_minutes" type="number" min="0" max="480" />
                        </InputField>
                    </div>
                </CardContent>
            </Card>
        </form>

        <Card class="mt-8">
            <CardHeader>
                <CardTitle>{{ t('app.settings.activity_section.reconstruction_title') }}</CardTitle>
                <CardDescription>{{ t('app.settings.activity_section.reconstruction_description') }}</CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 pt-0">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium">{{ t('app.dashboard.reconstruct_today') }}</span>
                        <span class="text-xs text-muted-foreground">{{ t('app.settings.activity_section.reconstruct_today_description') }}</span>
                    </div>
                    <ReconstructDialog>
                        <Button variant="outline" size="sm">{{ t('app.timeline.reconstruct') }}</Button>
                    </ReconstructDialog>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium">{{ t('app.timeline.reconstruct_since_date') }}</span>
                        <span class="text-xs text-muted-foreground">{{ t('app.settings.activity_section.reconstruct_from_date_description') }}</span>
                    </div>
                    <ReconstructDialog batch>
                        <Button variant="outline" size="sm">{{ t('app.timeline.reconstruct') }}</Button>
                    </ReconstructDialog>
                </div>
            </CardContent>
        </Card>
    </SettingsLayout>
</template>
