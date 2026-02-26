<script setup lang="ts">
defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="flex min-h-9 items-center justify-between gap-4">
        <div>
            <slot name="breadcrumb" />
            <h1 v-if="title" class="text-xl font-semibold" :class="{ 'mt-6': $slots.breadcrumb }">{{ title }}</h1>
            <div v-else-if="$slots.title" :class="{ 'mt-6': $slots.breadcrumb }">
                <slot name="title" />
            </div>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
