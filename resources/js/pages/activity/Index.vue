<script setup lang="ts">
import ActivityLog from '@/components/ActivityLog.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Activity">
        <template #header>
            <PageHeader title="Activity" />
        </template>

        <ActivityLog />
    </AppLayout>
</template>
