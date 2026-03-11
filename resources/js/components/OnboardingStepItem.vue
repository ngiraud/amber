<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2Icon, CircleIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { OnboardingStep } from '@/types';

defineProps<{
    step: OnboardingStep;
    action?: () => void;
}>();
</script>

<template>
    <div class="flex items-start gap-3 py-2">
        <div class="mt-0.5 shrink-0">
            <CheckCircle2Icon v-if="step.complete" class="size-4.5 text-primary" />
            <CircleIcon v-else class="size-4.5 text-muted-foreground/40" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span :class="['text-sm font-medium', step.complete ? 'text-muted-foreground line-through' : 'text-foreground']">
                    {{ step.label }}
                </span>
                <span
                    v-if="step.optional"
                    class="rounded-sm bg-muted px-1.5 py-0.5 text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Optional
                </span>
            </div>
            <p class="mt-0.5 text-xs text-muted-foreground">{{ step.description }}</p>
        </div>

        <Button v-if="!step.complete && action" variant="link" @click="action"> Go → </Button>
        <Button v-else-if="!step.complete" :as="Link" :href="step.url" variant="link"> Go → </Button>
    </div>
</template>
