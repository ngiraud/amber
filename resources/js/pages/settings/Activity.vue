<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import * as activityRoutes from '@/routes/settings/activity';
import type { ActivitySettings } from '@/types';

const props = defineProps<{
    activitySettings: ActivitySettings;
}>();

const form = useForm({
    idle_timeout_minutes: props.activitySettings.idle_timeout_minutes ?? 30,
    scan_interval_minutes: props.activitySettings.scan_interval_minutes ?? 2,
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
                    <CardTitle>Activity</CardTitle>
                    <CardDescription>Configure timing thresholds for session detection and scanning</CardDescription>
                    <CardAction>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="flex flex-col gap-4 pt-0">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Timing</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField
                            label="Idle timeout (min)"
                            :error="form.errors.idle_timeout_minutes"
                            hint="Gap between activity events that separates two reconstruction blocks"
                        >
                            <Input v-model.number="form.idle_timeout_minutes" type="number" min="1" max="120" />
                        </InputField>

                        <InputField
                            label="Scan interval (min)"
                            :error="form.errors.scan_interval_minutes"
                            hint="How often activity sources are scanned"
                        >
                            <Input v-model.number="form.scan_interval_minutes" type="number" min="1" max="30" />
                        </InputField>

                        <InputField
                            label="Block end padding (min)"
                            :error="form.errors.block_end_padding_minutes"
                            hint="Minutes added after the last event when reconstructing sessions"
                        >
                            <Input v-model.number="form.block_end_padding_minutes" type="number" min="0" max="60" />
                        </InputField>

                        <InputField
                            label="Manual session reminder (min)"
                            :error="form.errors.manual_session_reminder_minutes"
                            hint="Get a notification reminder when a manual timer session is running. Set to 0 to disable."
                        >
                            <Input v-model.number="form.manual_session_reminder_minutes" type="number" min="0" max="480" />
                        </InputField>
                    </div>
                </CardContent>
            </Card>
        </form>
    </SettingsLayout>
</template>
