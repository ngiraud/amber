<script setup lang="ts">
import { CalendarDaysIcon, ClockIcon, BarChart2Icon, TargetIcon, TrendingUpIcon } from 'lucide-vue-next';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Separator } from '@/components/ui/separator';
import { formatMinutes } from '@/lib/utils';
import type { ProjectBreakdown } from '@/types';

defineProps<{
    totalMinutes: number;
    workedDays: number;
    avgMinutesPerDay: number;
    avgMinutesPerWeek: number;
    currentWeekMinutes: number | null;
    projectBreakdown: ProjectBreakdown[];
}>();
</script>

<template>
    <div class="rounded-xl border bg-card px-6 shadow-sm ring-1 ring-border/5 ring-inset">
        <div class="flex items-center gap-10 py-4">
            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><CalendarDaysIcon /></StatItemIcon>
                    This Month
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(totalMinutes)" />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon class="-mt-px"><TargetIcon /></StatItemIcon>
                    Days Worked
                </StatItemLabel>
                <StatItemValue :value="String(workedDays)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><TrendingUpIcon /></StatItemIcon>
                    Avg / Day
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(avgMinutesPerDay)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><BarChart2Icon /></StatItemIcon>
                    Avg / Week
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(avgMinutesPerWeek)" muted />
            </StatItem>

            <template v-if="currentWeekMinutes !== null">
                <Separator orientation="vertical" class="h-8 opacity-0" />

                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        This Week
                    </StatItemLabel>
                    <StatItemValue :value="formatMinutes(currentWeekMinutes)" muted />
                </StatItem>
            </template>
        </div>

        <template v-if="projectBreakdown.length > 0">
            <Separator class="opacity-60" />
            <div class="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="project in projectBreakdown" :key="project.id ?? project.name ?? ''" class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-1.5">
                            <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color ?? '#94a3b8' }" />
                            <span class="truncate text-[10px] font-semibold text-muted-foreground">{{ project.name ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-[10px] text-muted-foreground/60">{{ project.percentage }}%</span>
                            <span class="font-mono text-xs font-bold tabular-nums">{{ formatMinutes(project.minutes) }}</span>
                        </div>
                    </div>
                    <div class="relative h-1 w-full overflow-hidden rounded-full bg-muted/50">
                        <div
                            class="h-full rounded-full transition-all duration-700 ease-out"
                            :style="{ backgroundColor: project.color ?? '#94a3b8', width: `${project.percentage}%` }"
                        />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
