<script setup lang="ts">
import { computed } from 'vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { TimelineDay } from '@/types';

const props = defineProps<{
    year: number;
    month: number;
    days: TimelineDay[];
}>();

const emit = defineEmits<{
    (e: 'select', date: string): void;
}>();

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

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

    // Monday-first: getDay() returns 0=Sun, so we shift
    const firstDow = (firstDay.getDay() + 6) % 7;

    const cells: Array<{ date: string | null; day: number | null }> = [];

    // Leading empty cells
    for (let i = 0; i < firstDow; i++) {
        cells.push({ date: null, day: null });
    }

    // Month days
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = `${props.year}-${String(props.month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({ date, day: d });
    }

    // Trailing empty cells to complete the last week
    while (cells.length % 7 !== 0) {
        cells.push({ date: null, day: null });
    }

    return cells;
});

function formatMinutes(minutes: number): string {
    if (minutes === 0) return '';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}`;
}

function isWeekend(date: string): boolean {
    const d = new Date(date + 'T00:00:00');
    return d.getDay() === 0 || d.getDay() === 6;
}

function isFuture(date: string): boolean {
    return date > today;
}
</script>

<template>
    <div class="select-none">
        <div class="mb-2 grid grid-cols-7 gap-px">
            <div v-for="day in WEEKDAYS" :key="day" class="py-1 text-center text-xs font-medium text-muted-foreground">
                {{ day }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg border bg-border">
            <div
                v-for="(cell, i) in calendarDays"
                :key="i"
                class="min-h-16 bg-card p-1.5"
                :class="{
                    'cursor-pointer transition-colors hover:bg-accent': cell.date !== null && !isFuture(cell.date),
                    'opacity-30': cell.date !== null && isFuture(cell.date),
                    'ring-2 ring-primary ring-inset': cell.date === today,
                }"
                @click="cell.date && !isFuture(cell.date) && emit('select', cell.date)"
            >
                <template v-if="cell.date !== null">
                    <p
                        class="text-xs leading-none font-medium"
                        :class="{
                            'text-muted-foreground/50': isWeekend(cell.date),
                            'font-bold text-primary': cell.date === today,
                        }"
                    >
                        {{ cell.day }}
                    </p>

                    <template v-if="daysMap.has(cell.date) && daysMap.get(cell.date)!.total_minutes > 0">
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ formatMinutes(daysMap.get(cell.date)!.total_minutes) }}
                        </p>

                        <div class="mt-1 flex flex-wrap gap-0.5">
                            <template v-for="project in daysMap.get(cell.date)!.projects" :key="project.id">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span
                                            :key="project.id"
                                            :title="project.name"
                                            class="block size-2 rounded-full"
                                            :style="{ backgroundColor: project.color }"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>{{ project.name }}</TooltipContent>
                                </Tooltip>
                            </template>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>
