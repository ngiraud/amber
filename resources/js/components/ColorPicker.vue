<script setup lang="ts">
defineProps<{
    name?: string;
}>();

const model = defineModel<string>({ default: '#6366f1' });
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="h-8 w-8 shrink-0 rounded-md border border-gray-300 shadow-sm"
            :style="{ backgroundColor: model }"
        />

        <input
            v-model="model"
            type="color"
            class="h-8 w-10 shrink-0 cursor-pointer rounded border border-gray-300 p-0.5"
        />

        <input
            v-model="model"
            :name="name"
            type="text"
            maxlength="7"
            placeholder="#000000"
            class="w-24 rounded-md border border-gray-300 px-2 py-1.5 font-mono text-sm focus:border-gray-400 focus:outline-none"
        />
    </div>
</template>
