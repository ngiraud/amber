<script setup lang="ts">
import { LucideRefreshCcw } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import SourceCard from '@/components/settings/SourceCard.vue';
import SyncActivityDialog from '@/components/SyncActivityDialog.vue';
import { Button } from '@/components/ui/button';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { useSpotlight } from '@/composables/useSpotlight';
import type { CategoryWithSources } from '@/types';

defineProps<{
    categories: CategoryWithSources[];
    hasEnabledSources: boolean;
}>();

const { spotlightClass } = useSpotlight();
const syncDialog = useTemplateRef<InstanceType<typeof SyncActivityDialog>>('syncDialog');

function showSyncDialog(): void {
    syncDialog.value?.show();
}
</script>

<template>
    <SettingsLayout active-tab="sources">
        <div :class="['flex flex-col gap-10', spotlightClass('sources')]">
            <section class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-sm font-semibold text-foreground/80">Synchronize</h2>
                    <p class="text-xs text-muted-foreground">
                        {{
                            hasEnabledSources
                                ? 'Force a scan of your sources to find activity events from the past.'
                                : 'Enable at least one source below to start syncing historical activity.'
                        }}
                    </p>
                </div>

                <Item variant="muted" :class="['flex items-center justify-between gap-4 p-4', spotlightClass('sync', '')]">
                    <div class="flex items-center gap-3">
                        <ItemMedia>
                            <LucideRefreshCcw class="size-5 text-muted-foreground" :class="{ 'opacity-40': !hasEnabledSources }" />
                        </ItemMedia>
                        <ItemContent>
                            <ItemTitle>Synchronize activity</ItemTitle>
                            <ItemDescription>Manually scan your enabled sources for a specific period.</ItemDescription>
                        </ItemContent>
                    </div>
                    <Button variant="outline" size="sm" :disabled="!hasEnabledSources" @click="showSyncDialog">Open Sync Tool</Button>
                </Item>
            </section>

            <section v-for="group in categories" :key="group.category.value" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-sm font-semibold text-foreground/80">{{ group.category.label }}</h2>
                    <p class="text-xs text-muted-foreground">{{ group.category.description }}</p>
                </div>

                <div :class="['grid gap-4 rounded-lg', group.category.display_layout === 'grid-2' ? 'grid-cols-2' : 'grid-cols-1']">
                    <div v-for="source in group.sources" :key="source.value" class="h-full">
                        <SourceCard :source="source" class="h-full" />
                    </div>
                </div>
            </section>
        </div>

        <SyncActivityDialog ref="syncDialog" />
    </SettingsLayout>
</template>
