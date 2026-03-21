<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import OnboardingStepItem from '@/components/OnboardingStepItem.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import { t } from '@/composables/useTranslation';
import * as onboardingRoutes from '@/routes/onboarding';
import type { OnboardingState, OnboardingStep } from '@/types';

const props = defineProps<{
    onboarding: OnboardingState;
}>();

const completedCount = computed(() => (props.onboarding.steps ?? []).filter((s) => s.complete).length);
const totalCount = computed(() => (props.onboarding.steps || []).length);
const progressPercent = computed(() => Math.round((completedCount.value / totalCount.value) * 100));

const { shouldOpen: shouldOpenSession } = useOpenSessionDialog();

function actionFor(step: OnboardingStep): (() => void) | undefined {
    if (step.key === 'sessions') {
        return () => {
            shouldOpenSession.value = true;
        };
    }

    return undefined;
}

function actionLabelFor(step: OnboardingStep): string | undefined {
    if (step.key === 'sessions') {
        return t('app.onboarding.log_manually');
    }

    if (step.key === 'sync') {
        return t('app.onboarding.sync_now');
    }

    return undefined;
}

function dismiss() {
    router.post(onboardingRoutes.dismiss().url, {}, { preserveScroll: true });
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ t('app.onboarding.get_started') }}</CardTitle>
            <CardDescription>{{ t('app.onboarding.steps_completed', { done: completedCount, total: totalCount }) }}</CardDescription>
            <CardAction>
                <Button variant="ghost" size="sm" class="text-muted-foreground" @click="dismiss"> {{ t('app.onboarding.dismiss') }} </Button>
            </CardAction>
        </CardHeader>

        <CardContent>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-primary transition-all duration-500" :style="{ width: `${progressPercent}%` }" />
            </div>

            <div class="mt-6 flex flex-col gap-2">
                <OnboardingStepItem
                    v-for="step in onboarding.steps"
                    :key="step.key"
                    :step="step"
                    :action="actionFor(step)"
                    :action-label="actionLabelFor(step)"
                />
            </div>
        </CardContent>
    </Card>
</template>
