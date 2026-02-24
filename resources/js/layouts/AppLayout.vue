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
        <SidebarInset class="overflow-y-auto">
            <ActiveSessionBanner />
            <main class="p-8">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>

    <Toaster richColors />
    <FlashMessage />
</template>
