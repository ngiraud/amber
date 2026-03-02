<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import StartSessionDialog from '@/components/StartSessionDialog.vue';
import TimeEntryRow from '@/components/TimeEntryRow.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import type { Paginator, Project, Session } from '@/types';
import { Empty, EmptyTitle } from '@/components/ui/empty';

defineProps<{
    sessions: Paginator<Session>;
    projects: Project[];
}>();
</script>

<template>
    <AppLayout title="Sessions">
        <template #header>
            <PageHeader title="Sessions">
                <template #actions>
                    <StartSessionDialog :projects="projects">
                        <Button size="sm">Start Session</Button>
                    </StartSessionDialog>
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
                <TimeEntryRow
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
