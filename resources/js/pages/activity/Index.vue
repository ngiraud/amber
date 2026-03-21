<script setup lang="ts">
import ActivityLog from '@/components/ActivityLog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout :title="t('app.nav.activities')">
        <template #header>
            <PageHeader :title="t('app.nav.activities')" />
        </template>

        <ActivityLog />
    </AppLayout>
</template>
