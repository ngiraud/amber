<script setup lang="ts">
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    error?: string;
    required?: boolean;
    hint?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label>
            {{ label }}
            <span v-if="required" class="ml-0.5 text-destructive">*</span>
        </Label>

        <slot />

        <p v-if="hint && !error" class="text-xs text-muted-foreground">{{ hint }}</p>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
