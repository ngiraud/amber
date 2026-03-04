<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as settingsRoutes from '@/routes/settings';

defineProps<{
    activeTab: 'general' | 'activity' | 'sources';
}>();

const navItems = [
    { label: 'General', href: settingsRoutes.general().url, tab: 'general' },
    { label: 'Activity', href: settingsRoutes.activity().url, tab: 'activity' },
    { label: 'Sources', href: settingsRoutes.sources().url, tab: 'sources' },
] as const;
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <PageHeader title="Settings" />
        </template>

        <div class="flex gap-10">
            <!-- Vertical nav -->
            <nav class="w-44 shrink-0">
                <ul class="flex flex-col gap-0.5">
                    <li v-for="item in navItems" :key="item.tab">
                        <Link
                            :href="item.href"
                            class="block rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === item.tab
                                    ? 'bg-accent text-accent-foreground'
                                    : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                            "
                        >
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Content -->
            <div class="min-w-0 flex-1">
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
