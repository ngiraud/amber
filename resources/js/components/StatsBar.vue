<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <div :class="cn('rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-border/5 ring-inset', props.class)">
        <slot />
    </div>
</template>
