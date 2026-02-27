<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionDetailSheet from '@/components/SessionDetailSheet.vue';
import TimeEntryRow from '@/components/TimeEntryRow.vue';
import TimeEntrySheet from '@/components/TimeEntrySheet.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { ActivityEvent, Paginator, Project, Session } from '@/types';

const props = defineProps<{
    date: string;
    previous_date: string;
    next_date: string;
    sessions: Session[];
    total_minutes: number;
    projects: Project[];
    selectedSession: Session | null;
    events?: Paginator<ActivityEvent> | null;
    hasNewEvents?: boolean;
}>();

const activeSessionId = ref<string | null>(props.selectedSession?.id ?? null);

watch(() => props.selectedSession?.id, (id) => {
    activeSessionId.value = id ?? null;
});

const dateLabel = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const formattedTotal = computed(() => {
    const h = Math.floor(props.total_minutes / 60);
    const m = props.total_minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
});

function navigate(direction: -1 | 1): void {
    const date = direction === -1 ? props.previous_date : props.next_date;
    router.get(timelineRoutes.show({ date }).url);
}

function reconstruct(): void {
    router.post(
        sessionRoutes.reconstruct().url,
        { date: props.date },
        {
            preserveScroll: true,
        },
    );
}

function onSessionOpen(session: Session): void {
    if (activeSessionId.value === session.id) return;

    activeSessionId.value = session.id;

    router.get(
        timelineRoutes.show({ date: props.date, session: session }).url,
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
        timelineRoutes.show({ date: props.date, session: undefined }).url,
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
    <AppLayout :title="dateLabel">
        <template #header>
            <PageHeader :title="dateLabel">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="reconstruct">
                            <RefreshCwIcon class="mr-1.5 size-3.5" />
                            Reconstruct
                        </Button>

                        <TimeEntrySheet :date="date" :projects="projects">
                            <Button size="sm">Add Session</Button>
                        </TimeEntrySheet>

                        <Button variant="ghost" size="icon" @click="navigate(-1)">
                            <ChevronLeftIcon class="size-4" />
                        </Button>
                        <Button variant="ghost" size="icon" @click="navigate(1)">
                            <ChevronRightIcon class="size-4" />
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div v-if="sessions.length === 0" class="mt-6 text-center">
            <p class="text-sm text-muted-foreground">No sessions for this day.</p>
            <p class="mt-1 text-xs text-muted-foreground">Start a session or add a manual one.</p>
        </div>

        <div v-else class="flex flex-col gap-1.5">
            <SessionDetailSheet
                v-for="session in sessions"
                :key="session.id"
                :session="session"
                :open="activeSessionId === session.id"
                :events="selectedSession?.id === session.id ? events : undefined"
                :has-new-events="selectedSession?.id === session.id ? hasNewEvents : false"
                events-prop-name="events"
                @update:open="(isOpen: boolean) => isOpen ? onSessionOpen(session) : onSessionClose()"
            >
                <TimeEntryRow :session="session" class="cursor-pointer" />
            </SessionDetailSheet>

            <div class="mt-3 flex justify-end border-t pt-3">
                <p class="text-sm font-medium">
                    Total: <span class="font-mono">{{ formattedTotal }}</span>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
