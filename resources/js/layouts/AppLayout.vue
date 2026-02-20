<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppSidebar from '@/components/AppSidebar.vue';
import FlashMessage from '@/components/FlashMessage.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="flex h-screen overflow-hidden bg-gray-50 text-gray-900">
        <AppSidebar />

        <div class="flex flex-1 flex-col overflow-hidden">
            <main class="flex-1 overflow-y-auto p-8">
                <slot />
            </main>
        </div>

        <FlashMessage />
    </div>
</template>
