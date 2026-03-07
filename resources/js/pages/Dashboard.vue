<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDaysIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import StartSessionDialog from '@/components/StartSessionDialog.vue';
import TimeEntryRow from '@/components/TimeEntryRow.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { Project, Session } from '@/types';

const props = defineProps<{
    date: string;
    sessions: Session[];
    total_minutes: number;
    week_minutes: number;
    month_minutes: number;
    projects: Project[];
}>();

function formatMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (minutes === 0) return '0h';
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

const dateLabel = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <PageHeader title="Dashboard">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="timelineRoutes.index().url">
                                <CalendarDaysIcon class="mr-1.5 size-3.5" />
                                Timeline
                            </Link>
                        </Button>

                        <StartSessionDialog :projects="projects">
                            <Button size="sm">Add Session</Button>
                        </StartSessionDialog>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="mb-6 grid grid-cols-3 gap-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Today</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(total_minutes) }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ dateLabel }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">This Week</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(week_minutes) }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">This Month</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(month_minutes) }}</p>
                </CardContent>
            </Card>
        </div>

        <Empty v-if="sessions.length === 0" class="mt-4">
            <EmptyDescription>No sessions today yet.</EmptyDescription>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <Link v-for="session in sessions" :key="session.id" :href="sessionRoutes.show({ session: session }).url">
                <TimeEntryRow :session="session" />
            </Link>
        </div>
    </AppLayout>
</template>
