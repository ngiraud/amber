<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppSidebar from '@/components/AppSidebar.vue';
import CommandPalette from '@/components/CommandPalette.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import TitleBar from '@/components/TitleBar.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { useNativeAppEvents } from '@/composables/useNativeAppEvents';

defineProps<{
    title?: string;
    breadcrumb?: string[];
}>();

useNativeAppEvents();
</script>

<template>
    <Head :title="title" />

    <div class="flex h-screen flex-col overflow-hidden">
        <TitleBar :title="title" :breadcrumb="breadcrumb" />

        <SidebarProvider class="flex-1 overflow-hidden" :open="false">
            <AppSidebar />
            <SidebarInset class="overflow-hidden">
                <header v-if="$slots.header" class="shrink-0 bg-background px-8 pt-8 pb-4">
                    <slot name="header" />
                </header>
                <div class="flex min-h-0 flex-1 flex-col overflow-y-auto px-8" :class="$slots.header ? 'py-6' : 'py-8'">
                    <slot />
                </div>
            </SidebarInset>
        </SidebarProvider>
    </div>

    <CommandPalette />
    <Toaster />
    <FlashMessage />
</template>
