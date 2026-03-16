<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import MonthCalendar from '@/components/MonthCalendar.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import TimelineStatsBar from '@/components/TimelineStatsBar.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatPeriod } from '@/lib/utils';
import * as timelineRoutes from '@/routes/timeline';
import type { TimelineDay, TimelineMonthStats, WeekStats } from '@/types';

const props = defineProps<{
    year: number;
    month: number;
    days: TimelineDay[];
    stats: TimelineMonthStats;
    weeks: WeekStats[];
}>();

const monthLabel = computed(() => formatPeriod(props.month, props.year));

function navigate(direction: -1 | 1): void {
    let month = props.month + direction;
    let year = props.year;

    if (month < 1) {
        month = 12;
        year -= 1;
    } else if (month > 12) {
        month = 1;
        year += 1;
    }

    router.get(timelineRoutes.index({ query: { year, month } }).url);
}

function selectDay(date: string): void {
    router.get(timelineRoutes.show({ date: date }).url);
}

const fromDateDialog = ref<InstanceType<typeof ReconstructDialog> | null>(null);

function onKeyDown(e: KeyboardEvent): void {
    if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
        return;
    }

    if (e.key === 'ArrowLeft') {
        navigate(-1);
    }

    if (e.key === 'ArrowRight') {
        navigate(1);
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeyDown);

    const params = new URLSearchParams(window.location.search);
    const reconstructFrom = params.get('reconstruct_from');

    if (reconstructFrom) {
        fromDateDialog.value?.show(reconstructFrom);
        window.history.replaceState({}, '', timelineRoutes.index().url);
    }
});

onUnmounted(() => window.removeEventListener('keydown', onKeyDown));
</script>

<template>
    <AppLayout title="Timeline">
        <template #header>
            <PageHeader title="Timeline">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <ReconstructDialog ref="fromDateDialog" batch>
                            <Button variant="outline" size="sm">
                                <RefreshCwIcon class="mr-1.5 size-3.5" />
                                Reconstruct since a date
                            </Button>
                        </ReconstructDialog>

                        <Button variant="ghost" size="icon" @click="navigate(-1)">
                            <ChevronLeftIcon class="size-4" />
                        </Button>
                        <span class="min-w-36 text-center text-sm font-medium">{{ monthLabel }}</span>
                        <Button variant="ghost" size="icon" @click="navigate(1)">
                            <ChevronRightIcon class="size-4" />
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="flex flex-col gap-6">
            <TimelineStatsBar
                :total-minutes="stats.month_total_minutes"
                :worked-days="stats.month_worked_days"
                :avg-minutes-per-day="stats.month_avg_minutes_per_day"
                :avg-minutes-per-week="stats.month_avg_minutes_per_week"
                :current-week-minutes="stats.current_week_total_minutes"
                :project-breakdown="stats.month_project_breakdown"
                class="mt-2"
            />

            <MonthCalendar :year="year" :month="month" :days="days" :weeks="weeks" @select="selectDay" />
        </div>
    </AppLayout>
</template>
