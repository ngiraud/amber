<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import * as settingsRoutes from '@/routes/settings';

defineProps<{
    activeTab: 'general' | 'activity' | 'sources' | 'ai';
}>();

const navItems = [
    { labelKey: 'app.settings.general', href: settingsRoutes.general().url, tab: 'general' },
    { labelKey: 'app.settings.activity', href: settingsRoutes.activity().url, tab: 'activity' },
    { labelKey: 'app.settings.sources', href: settingsRoutes.sources().url, tab: 'sources' },
    { labelKey: 'app.settings.ai', href: settingsRoutes.ai().url, tab: 'ai' },
] as const;
</script>

<template>
    <AppLayout :title="t('app.settings.title')">
        <template #header>
            <PageHeader :title="t('app.settings.title')" />
        </template>

        <div class="flex gap-10">
            <!-- Vertical nav -->
            <nav class="sticky top-0 w-44 shrink-0 self-start">
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
                            {{ t(item.labelKey) }}
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
