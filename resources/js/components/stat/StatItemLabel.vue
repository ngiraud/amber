<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <div :class="cn('flex items-center gap-1.5 text-[9px] font-black tracking-widest text-muted-foreground/80 uppercase', props.class)">
        <slot />
    </div>
</template>
