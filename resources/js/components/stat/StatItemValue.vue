<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        value: string;
        muted?: boolean;
        active?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    { muted: false, active: false },
);
</script>

<template>
    <div :class="cn('flex items-baseline gap-2.5', props.class)">
        <p :class="['font-mono text-2xl font-black tracking-tighter', props.muted && 'opacity-70', props.active && 'text-primary']">
            {{ value }}
        </p>
        <slot />
    </div>
</template>
