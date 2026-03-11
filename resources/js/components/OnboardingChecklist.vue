<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import OnboardingStepItem from '@/components/OnboardingStepItem.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
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
    if (step.key === 'start-session') {
        return () => {
            shouldOpenSession.value = true;
        };
    }

    return undefined;
}

function dismiss() {
    router.post(onboardingRoutes.dismiss().url, {}, { preserveScroll: true });
}
</script>

<template>
    <Card class="mb-6">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base">Get started with Activity Record</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">{{ completedCount }} of {{ totalCount }} steps completed</p>
                </div>
                <Button variant="ghost" size="sm" class="text-muted-foreground" @click="dismiss"> Dismiss </Button>
            </div>

            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-primary transition-all duration-500" :style="{ width: `${progressPercent}%` }" />
            </div>
        </CardHeader>

        <CardContent class="pt-0">
            <div class="divide-y divide-border">
                <OnboardingStepItem v-for="step in onboarding.steps" :key="step.key" :step="step" :action="actionFor(step)" />
            </div>
        </CardContent>
    </Card>
</template>
