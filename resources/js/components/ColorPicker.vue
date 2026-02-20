<script setup lang="ts">
import { Input } from '@/components/ui/input';

defineProps<{
    name?: string;
}>();

const model = defineModel<string>({ default: '#6366f1' });
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="h-9 w-9 shrink-0 rounded-md border shadow-xs" :style="{ backgroundColor: model }" />

        <input v-model="model" type="color" class="h-9 w-10 shrink-0 cursor-pointer rounded-md border p-0.5" />

        <Input v-model="model" :name="name" type="text" maxlength="7" placeholder="#000000" class="w-28 font-mono" />
    </div>
</template>
