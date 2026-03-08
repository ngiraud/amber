<script setup lang="ts">
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    description?: string;
    error?: string;
    required?: boolean;
    hint?: string;
    direction?: 'vertical' | 'horizontal';
}>();
</script>

<template>
    <div :class="direction === 'horizontal' ? 'flex items-center justify-between gap-4' : 'flex flex-col gap-1.5'">
        <div class="flex flex-col gap-0.5">
            <Label>
                {{ label }}
                <span v-if="required" class="ml-0.5 text-destructive">*</span>
            </Label>
            <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
        </div>

        <slot />

        <template v-if="direction !== 'horizontal'">
            <p v-if="hint && !error" class="text-xs text-muted-foreground">{{ hint }}</p>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </div>
</template>
