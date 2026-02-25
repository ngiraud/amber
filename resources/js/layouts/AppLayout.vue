<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ActiveSessionBanner from '@/components/ActiveSessionBanner.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title" />

    <SidebarProvider class="h-screen overflow-hidden" :open="false">
        <AppSidebar />
        <SidebarInset class="overflow-hidden">
            <ActiveSessionBanner />
            <header v-if="$slots.header" class="shrink-0 bg-background px-8 pt-8 pb-4">
                <slot name="header" />
            </header>
            <div class="min-h-0 flex-1 flex flex-col overflow-y-auto px-8" :class="$slots.header ? 'py-6' : 'py-8'">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>

    <Toaster richColors />
    <FlashMessage />
</template>
