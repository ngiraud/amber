<script setup lang="ts">
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import SourceCard from '@/components/settings/SourceCard.vue';
import type { SourceDefinition } from '@/types';

defineProps<{
    sources: SourceDefinition[];
}>();
</script>

<template>
    <SettingsLayout active-tab="sources">
        <div class="flex flex-col gap-4">
            <SourceCard v-for="source in sources" :key="source.value" :source="source" />
        </div>
    </SettingsLayout>
</template>
