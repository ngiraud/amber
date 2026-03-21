<script setup lang="ts">
import { computed } from 'vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { locale, t } from '@/composables/useTranslation';
import { formatMinutes } from '@/lib/utils';
import type { TimelineDay, WeekStats } from '@/types';

const props = defineProps<{
    year: number;
    month: number;
    days: TimelineDay[];
    weeks?: WeekStats[];
}>();

const emit = defineEmits<{
    (e: 'select', date: string): void;
}>();

// Jan 1 2024 is a Monday — iterate 7 days to get Mon→Sun in ISO week order
const WEEKDAYS = computed(() =>
    Array.from({ length: 7 }, (_, i) => new Intl.DateTimeFormat(locale.value, { weekday: 'short' }).format(new Date(2024, 0, 1 + i))),
);

const today = new Date().toISOString().split('T')[0];

const daysMap = computed(() => {
    const map = new Map<string, TimelineDay>();

    for (const day of props.days) {
        map.set(day.date, day);
    }

    return map;
});

const calendarDays = computed(() => {
    const firstDay = new Date(props.year, props.month - 1, 1);
    const lastDay = new Date(props.year, props.month, 0);

    const firstDow = (firstDay.getDay() + 6) % 7;

    const cells: Array<{ date: string | null; day: number | null }> = [];

    for (let i = 0; i < firstDow; i++) {
        cells.push({ date: null, day: null });
    }

    for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = `${props.year}-${String(props.month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({ date, day: d });
    }

    while (cells.length % 7 !== 0) {
        cells.push({ date: null, day: null });
    }

    return cells;
});

type CalendarCell = { date: string | null; day: number | null };

const calendarWeeksWithStats = computed(() => {
    const cells = calendarDays.value;
    const result: Array<{ cells: CalendarCell[]; stats: WeekStats | null }> = [];

    for (let i = 0; i < cells.length; i += 7) {
        const weekCells = cells.slice(i, i + 7);
        let stats: WeekStats | null = null;

        if (props.weeks) {
            for (const cell of weekCells) {
                if (cell.date) {
                    const found = props.weeks.find((w) => cell.date! >= w.start_date && cell.date! <= w.end_date);

                    if (found) {
                        stats = found;
                        break;
                    }
                }
            }
        }

        result.push({ cells: weekCells, stats });
    }

    return result;
});

function isWeekend(date: string): boolean {
    const d = new Date(date + 'T00:00:00');

    return d.getDay() === 0 || d.getDay() === 6;
}

function isFuture(date: string): boolean {
    return date > today;
}
</script>

<template>
    <div class="w-full select-none">
        <!-- Header row -->
        <div class="mb-2 flex gap-2">
            <div class="grid min-w-0 flex-1 grid-cols-7 gap-px">
                <div v-for="day in WEEKDAYS" :key="day" class="py-1 text-center text-xs font-medium text-muted-foreground">
                    {{ day }}
                </div>
            </div>
            <div v-if="weeks !== undefined" class="w-36 shrink-0 py-1 text-center text-xs font-medium text-muted-foreground">
                {{ t('app.timeline.week') }}
            </div>
        </div>

        <!-- Calendar body -->
        <div class="flex gap-2">
            <!-- Day cells -->
            <div class="min-w-0 flex-1 overflow-hidden rounded-lg border bg-border">
                <div class="grid grid-cols-7 gap-px">
                    <template v-for="(weekRow, wi) in calendarWeeksWithStats" :key="`w${wi}`">
                        <template v-for="(cell, ci) in weekRow.cells" :key="`${wi}-${ci}`">
                            <Tooltip v-if="cell.date !== null && daysMap.get(cell.date)?.total_minutes" :delay-duration="200">
                                <TooltipTrigger as-child>
                                    <div
                                        class="group min-h-20 cursor-pointer bg-card p-1.5 transition-colors hover:bg-accent"
                                        :class="{
                                            'opacity-40': isFuture(cell.date),
                                            'ring-1 ring-primary ring-inset': cell.date === today,
                                        }"
                                        @click="!isFuture(cell.date) && emit('select', cell.date)"
                                    >
                                        <p
                                            class="text-xs leading-none font-medium group-hover:text-accent-foreground"
                                            :class="{
                                                'text-muted-foreground': isWeekend(cell.date),
                                                'font-bold text-primary': cell.date === today,
                                            }"
                                        >
                                            {{ cell.day }}
                                        </p>
                                        <p class="mt-1 font-mono text-xs text-muted-foreground group-hover:text-accent-foreground/80">
                                            {{ formatMinutes(daysMap.get(cell.date)!.total_minutes) }}
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-0.5">
                                            <span
                                                v-for="project in daysMap.get(cell.date)!.projects"
                                                :key="project.id"
                                                class="block size-2 rounded-full"
                                                :style="{ backgroundColor: project.color }"
                                            />
                                        </div>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent side="top" class="space-y-1.5 p-3">
                                    <p class="font-mono text-sm font-bold">{{ formatMinutes(daysMap.get(cell.date)!.total_minutes) }}</p>
                                    <div class="space-y-1">
                                        <div
                                            v-for="project in daysMap.get(cell.date)!.projects"
                                            :key="project.id"
                                            class="flex items-center gap-2 text-xs"
                                        >
                                            <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                                            <span class="text-muted-foreground">{{ project.name }}</span>
                                            <span class="ml-auto font-mono font-semibold tabular-nums">{{ formatMinutes(project.minutes) }}</span>
                                        </div>
                                    </div>
                                </TooltipContent>
                            </Tooltip>

                            <!-- Day cell: no sessions or padding -->
                            <div
                                v-else
                                class="group min-h-20 bg-card p-1.5"
                                :class="{
                                    'cursor-pointer transition-colors hover:bg-accent': cell.date !== null && !isFuture(cell.date),
                                    'opacity-40': cell.date === null || isFuture(cell.date),
                                    'ring-1 ring-primary ring-inset': cell.date === today,
                                }"
                                @click="cell.date && !isFuture(cell.date) && emit('select', cell.date)"
                            >
                                <p
                                    v-if="cell.date !== null"
                                    class="text-xs leading-none font-medium"
                                    :class="{
                                        'text-muted-foreground': isWeekend(cell.date),
                                        'font-bold text-primary': cell.date === today,
                                        'group-hover:text-accent-foreground': !isFuture(cell.date),
                                    }"
                                >
                                    {{ cell.day }}
                                </p>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            <!-- Week summary column -->
            <div v-if="weeks !== undefined" class="flex w-36 shrink-0 flex-col gap-px overflow-hidden rounded-lg border bg-border">
                <template v-for="(weekRow, wi) in calendarWeeksWithStats" :key="`ws${wi}`">
                    <Tooltip v-if="weekRow.stats" :delay-duration="150">
                        <TooltipTrigger as-child>
                            <div class="flex min-h-20 flex-1 cursor-default flex-col justify-center gap-1.5 bg-card p-2.5">
                                <p class="font-mono text-sm leading-none font-black tracking-tighter">
                                    {{ formatMinutes(weekRow.stats.total_minutes) }}
                                </p>
                                <div class="flex h-1 w-full overflow-hidden rounded-full">
                                    <div
                                        v-for="project in weekRow.stats.project_breakdown"
                                        :key="project.id ?? project.name ?? ''"
                                        class="h-full transition-all duration-700 ease-out"
                                        :style="{ backgroundColor: project.color ?? '#94a3b8', width: `${project.percentage}%` }"
                                    />
                                </div>
                                <p class="font-mono text-[9px] text-muted-foreground/60 tabular-nums">
                                    {{ weekRow.stats.worked_days }}{{ t('app.timeline.days_short') }} ·
                                    {{ formatMinutes(weekRow.stats.avg_minutes_per_day) }}{{ t('app.timeline.per_day_short') }}
                                </p>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent side="left" class="space-y-2 p-3">
                            <div class="flex items-baseline justify-between gap-4">
                                <p class="text-[10px] font-black tracking-widest text-muted-foreground/80 uppercase">{{ weekRow.stats.label }}</p>
                                <p class="font-mono text-sm font-bold">{{ formatMinutes(weekRow.stats.total_minutes) }}</p>
                            </div>
                            <div class="space-y-1">
                                <div
                                    v-for="project in weekRow.stats.project_breakdown"
                                    :key="project.id ?? project.name ?? ''"
                                    class="flex items-center gap-2 text-xs"
                                >
                                    <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color ?? '#94a3b8' }" />
                                    <span class="text-muted-foreground">{{ project.name ?? 'Unknown' }}</span>
                                    <span class="ml-auto font-mono font-semibold tabular-nums">{{ formatMinutes(project.minutes) }}</span>
                                    <span class="w-8 text-right font-mono text-[10px] text-muted-foreground/60 tabular-nums"
                                        >{{ project.percentage }}%</span
                                    >
                                </div>
                            </div>
                        </TooltipContent>
                    </Tooltip>

                    <!-- Inactive week row: same as padding day cell -->
                    <div v-else class="min-h-20 flex-1 bg-card opacity-40" />
                </template>
            </div>
        </div>
    </div>
</template>
