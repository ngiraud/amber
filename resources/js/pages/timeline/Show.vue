<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import TimeEntryRow from '@/components/TimeEntryRow.vue';
import TimeEntrySheet from '@/components/TimeEntrySheet.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { Project, Session } from '@/types';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';

const props = defineProps<{
    date: string;
    previous_date: string;
    next_date: string;
    sessions: Session[];
    total_minutes: number;
    projects: Project[];
}>();

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

        <Empty v-if="sessions.length === 0" class="mt-4">
            <EmptyTitle>No sessions for this day.</EmptyTitle>
            <EmptyDescription>Start a session or add a manual one.</EmptyDescription>

            <div class="flex gap-4">
                <Button variant="outline" size="sm" @click="reconstruct">
                    <RefreshCwIcon class="mr-1.5 size-3.5" />
                    Reconstruct
                </Button>
                <TimeEntrySheet :date="date" :projects="projects">
                    <Button size="sm">Add Session</Button>
                </TimeEntrySheet>
            </div>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <TimeEntryRow
                v-for="session in sessions"
                :key="session.id"
                :session="session"
                class="cursor-pointer"
                @click="router.visit(sessionRoutes.show(session).url)"
            />

            <div class="mt-3 flex justify-end border-t pt-3">
                <p class="text-sm font-medium">
                    Total: <span class="font-mono">{{ formattedTotal }}</span>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
