<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2Icon, CircleIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import type { OnboardingStep } from '@/types';

defineProps<{
    step: OnboardingStep;
    action?: () => void;
    actionLabel?: string;
}>();
</script>

<template>
    <Item variant="outline" size="sm">
        <ItemMedia>
            <CheckCircle2Icon v-if="step.complete" class="size-4.5 text-primary" />
            <CircleIcon v-else class="size-4.5 text-muted-foreground/40" />
        </ItemMedia>
        <ItemContent>
            <ItemTitle>
                <span :class="['text-sm font-medium', step.complete ? 'text-muted-foreground line-through' : 'text-foreground']">
                    {{ step.label }}
                </span>
                <span
                    v-if="step.optional"
                    class="rounded-sm bg-muted px-1.5 py-0.5 text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Optional
                </span>
            </ItemTitle>
            <ItemDescription>{{ step.description }}</ItemDescription>
        </ItemContent>
        <ItemActions>
            <Button v-if="!step.complete && action" variant="ghost" class="text-primary" @click="action"> {{ actionLabel ?? 'Go →' }} </Button>
            <Button v-else-if="!step.complete" :as="Link" :href="step.url" variant="ghost" class="text-primary"> {{ actionLabel ?? 'Go →' }} </Button>
        </ItemActions>
    </Item>
</template>
