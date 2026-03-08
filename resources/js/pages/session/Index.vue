<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import SessionRow from '@/components/SessionRow.vue';
import { Button } from '@/components/ui/button';
import { Empty, EmptyTitle } from '@/components/ui/empty';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import type { Paginator, Session } from '@/types';

defineProps<{
    sessions: Paginator<Session>;
}>();

const { shouldOpen } = useOpenSessionDialog();
</script>

<template>
    <AppLayout title="Sessions">
        <template #header>
            <PageHeader title="Sessions">
                <template #actions>
                    <Button size="sm" @click="shouldOpen = true">Add Session</Button>
                </template>
            </PageHeader>
        </template>

        <Empty v-if="(sessions?.data || []).length === 0" class="mt-6">
            <EmptyTitle>No sessions yet.</EmptyTitle>
        </Empty>

        <InfiniteScroll v-else data="sessions" :buffer="200">
            <template #loading>
                <div class="mt-1.5 h-[58px] animate-pulse rounded-lg border bg-card" />
            </template>

            <div class="flex flex-col gap-1.5">
                <SessionRow
                    v-for="session in sessions.data"
                    :key="session.id"
                    :session="session"
                    :show-date="true"
                    class="cursor-pointer"
                    @click="router.visit(sessionRoutes.show(session).url)"
                />
            </div>
        </InfiniteScroll>
    </AppLayout>
</template>
