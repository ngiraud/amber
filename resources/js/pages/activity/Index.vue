<script setup lang="ts">
import ActivityLog from '@/components/ActivityLog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ActivityEvent, Paginator } from '@/types';

defineProps<{
    events: Paginator<ActivityEvent>;
    hasNewEvents: boolean;
}>();
</script>

<template>
    <AppLayout title="Activity">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Activity</h1>
        </div>

        <div class="mt-6">
            <ActivityLog :events="events" :has-new-events="hasNewEvents" />
        </div>
    </AppLayout>
</template>
