<script setup lang="ts">
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import SourceCard from '@/components/settings/SourceCard.vue';
import { useSpotlight } from '@/composables/useSpotlight';
import type { CategoryWithSources } from '@/types';

defineProps<{
    categories: CategoryWithSources[];
}>();

const { spotlightClass } = useSpotlight();
</script>

<template>
    <SettingsLayout active-tab="sources">
        <div :class="['flex flex-col gap-10', spotlightClass('sources')]">
            <section v-for="group in categories" :key="group.category.value" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-sm font-semibold text-foreground/80">{{ group.category.label }}</h2>
                    <p class="text-xs text-muted-foreground">{{ group.category.description }}</p>
                </div>

                <div
                    :class="[
                        'grid gap-4 rounded-lg',
                        group.category.display_layout === 'grid-2' ? 'grid-cols-2' : 'grid-cols-1',
                    ]"
                >
                    <div v-for="source in group.sources" :key="source.value" class="h-full">
                        <SourceCard :source="source" class="h-full" />
                    </div>
                </div>
            </section>
        </div>
    </SettingsLayout>
</template>
