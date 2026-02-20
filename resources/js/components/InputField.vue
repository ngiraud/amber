<script setup lang="ts">
defineProps<{
    label: string;
    error?: string;
    required?: boolean;
    hint?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="ml-0.5 text-red-500">*</span>
        </label>

        <slot />

        <p v-if="hint && !error" class="text-xs text-gray-500">{{ hint }}</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
