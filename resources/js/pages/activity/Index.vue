<script setup lang="ts">
import ActivityLog from '@/components/ActivityLog.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ActivityEvent, Paginator } from '@/types';

defineProps<{
    events: Paginator<ActivityEvent>;
    hasNewEvents: boolean;
}>();
</script>

<template>
    <AppLayout title="Activity">
        <template #header>
            <PageHeader title="Activity" />
        </template>

        <ActivityLog :events="events" :has-new-events="hasNewEvents" />
    </AppLayout>
</template>
