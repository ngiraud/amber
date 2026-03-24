<script setup lang="ts">
import { CalendarDaysIcon, ClockIcon, BarChart2Icon, TargetIcon, TrendingUpIcon } from 'lucide-vue-next';
import ProjectBreakdownItem from '@/components/ProjectBreakdownItem.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Separator } from '@/components/ui/separator';
import { t } from '@/composables/useTranslation';
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
                    {{ t('app.dashboard.this_month') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(totalMinutes)" />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon class="-mt-px"><TargetIcon /></StatItemIcon>
                    {{ t('app.timeline.days_worked') }}
                </StatItemLabel>
                <StatItemValue :value="String(workedDays)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><TrendingUpIcon /></StatItemIcon>
                    {{ t('app.timeline.avg_per_day') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(avgMinutesPerDay)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><BarChart2Icon /></StatItemIcon>
                    {{ t('app.timeline.avg_per_week') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(avgMinutesPerWeek)" muted />
            </StatItem>

            <template v-if="currentWeekMinutes !== null">
                <Separator orientation="vertical" class="h-8 opacity-0" />

                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        {{ t('app.dashboard.this_week') }}
                    </StatItemLabel>
                    <StatItemValue :value="formatMinutes(currentWeekMinutes)" muted />
                </StatItem>
            </template>
        </div>

        <template v-if="projectBreakdown.length > 0">
            <Separator class="opacity-60" />
            <div class="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <ProjectBreakdownItem
                    v-for="project in projectBreakdown"
                    :key="project.id ?? project.name ?? ''"
                    :name="project.name"
                    :color="project.color"
                    :minutes="project.minutes"
                    :percentage="project.percentage"
                />
            </div>
        </template>
    </div>
</template>
