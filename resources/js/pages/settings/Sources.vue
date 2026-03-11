<script setup lang="ts">
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import SourceCard from '@/components/settings/SourceCard.vue';
import { useSpotlight } from '@/composables/useSpotlight';
import type { SourceDefinition } from '@/types';

defineProps<{
    sources: SourceDefinition[];
}>();

const { spotlightClass } = useSpotlight();
</script>

<template>
    <SettingsLayout active-tab="sources">
        <div :class="['grid grid-cols-2 gap-4 rounded-lg', spotlightClass('sources')]">
            <div v-for="source in sources" :key="source.value">
                <SourceCard :source="source" />
            </div>
        </div>
    </SettingsLayout>
</template>
