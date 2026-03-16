<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <span :class="cn('-mt-0.5 shrink-0 text-muted-foreground [&>svg]:size-3', props.class)">
        <slot />
    </span>
</template>
