<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionDetailSheet from '@/components/SessionDetailSheet.vue';
import StartSessionDialog from '@/components/StartSessionDialog.vue';
import TimeEntryRow from '@/components/TimeEntryRow.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import type { ActivityEvent, Paginator, Project, Session } from '@/types';

const props = defineProps<{
    sessions: Paginator<Session>;
    projects: Project[];
    selectedSession: Session | null;
    events?: Paginator<ActivityEvent> | null;
    hasNewEvents?: boolean;
}>();

const activeSessionId = ref<string | null>(props.selectedSession?.id ?? null);

watch(
    () => props.selectedSession?.id,
    (id) => {
        activeSessionId.value = id ?? null;
    },
);

function onSessionOpen(session: Session): void {
    if (activeSessionId.value === session.id) return;

    activeSessionId.value = session.id;

    router.get(
        sessionRoutes.index({ session }).url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['selectedSession', 'events', 'hasNewEvents'],
            reset: ['events'],
        },
    );
}

function onSessionClose(): void {
    activeSessionId.value = null;

    router.get(
        sessionRoutes.index().url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: [],
            reset: ['events'],
        },
    );
}
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

        <div v-if="(sessions?.data || []).length === 0" class="mt-6 text-center">
            <p class="text-sm text-muted-foreground">No sessions yet.</p>
        </div>

        <InfiniteScroll v-else data="sessions" :buffer="200">
            <template #loading>
                <div class="mt-1.5 h-[58px] animate-pulse rounded-lg border bg-card" />
            </template>

            <div class="flex flex-col gap-1.5">
                <SessionDetailSheet
                    v-for="session in sessions.data"
                    :key="session.id"
                    :session="session"
                    :open="activeSessionId === session.id"
                    :events="selectedSession?.id === session.id ? events : undefined"
                    :has-new-events="selectedSession?.id === session.id ? hasNewEvents : false"
                    events-prop-name="events"
                    @update:open="(isOpen: boolean) => (isOpen ? onSessionOpen(session) : onSessionClose())"
                >
                    <TimeEntryRow :session="session" :show-date="true" class="cursor-pointer" />
                </SessionDetailSheet>
            </div>
        </InfiniteScroll>
    </AppLayout>
</template>
