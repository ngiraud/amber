<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDaysIcon, ClockIcon, LayersIcon, RadioIcon, TargetIcon, TimerIcon, TimerResetIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import DaySummaryCard from '@/components/DaySummaryCard.vue';
import LogPastSessionSheet from '@/components/LogPastSessionSheet.vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionRow from '@/components/SessionRow.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import StatsBar from '@/components/StatsBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { useDateFormat } from '@/composables/useDateFormat';
import { useNow } from '@/composables/useNow';
import { locale, t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { OnboardingState, Session, SessionStats } from '@/types';

const props = defineProps<{
    date: string;
    sessions: Session[];
    session_stats: SessionStats;
    week_minutes: number;
    month_minutes: number;
}>();

const page = usePage();
const onboarding = computed(() => page.props.onboarding as OnboardingState);
const showChecklist = computed(() => !onboarding.value?.dismissed && !onboarding.value?.all_complete);

const logSessionOpen = ref(false);

let pollTimer: ReturnType<typeof setInterval>;

onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({ only: ['sessions', 'session_stats', 'week_minutes', 'month_minutes', 'activeSession'] });
    }, 30 * 1000);
});

onUnmounted(() => {
    clearInterval(pollTimer);
});

const { now, isToday: isTodayFn } = useNow();
const { formatTime } = useDateFormat();

const isToday = computed(() => isTodayFn(props.date));
const activeSession = computed(() => (isToday.value ? (page.props.activeSession as Session | null) : null));

const activeSessionMinutes = computed(() => {
    if (!activeSession.value) {
        return 0;
    }

    if (activeSession.value.rounded_minutes) {
        return activeSession.value.rounded_minutes;
    }

    const start = new Date(activeSession.value.started_at);
    const diff = now.value.getTime() - start.getTime();

    return Math.max(Math.floor(diff / 1000 / 60), 0);
});

const dateLabel = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    const str = d.toLocaleDateString(locale.value, { weekday: 'long', month: 'long', day: 'numeric' });

    return str.charAt(0).toUpperCase() + str.slice(1);
});

const workRange = computed(() => {
    if (!props.session_stats.first_started_at || !props.session_stats.last_ended_at) {
        return null;
    }

    return `${formatTime(props.session_stats.first_started_at)} → ${formatTime(props.session_stats.last_ended_at)}`;
});
</script>

<template>
    <AppLayout :title="t('app.dashboard.title')">
        <template #header>
            <PageHeader :title="t('app.dashboard.title')">
                <template #actions>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="timelineRoutes.index().url">
                            <CalendarDaysIcon class="mr-1.5 size-3.5" />
                            {{ t('app.nav.timeline') }}
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="flex flex-col gap-10">
            <!-- Compact Quick Stats -->
            <StatsBar class="flex items-center justify-between">
                <div class="flex items-center gap-10">
                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon><ClockIcon /></StatItemIcon>
                            {{ t('app.dashboard.today') }}
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(session_stats.total_minutes + activeSessionMinutes)" :active="!!activeSession">
                            <Badge v-if="activeSession" class="animate-pulse">
                                <RadioIcon class="size-3" />
                                <span class="text-[9px] font-black tracking-tighter tabular-nums"> LIVE </span>
                            </Badge>
                        </StatItemValue>
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon class="-mt-px"><TargetIcon /></StatItemIcon>
                            {{ t('app.dashboard.this_week') }}
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(week_minutes + (isToday ? activeSessionMinutes : 0))" muted />
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon><CalendarDaysIcon /></StatItemIcon>
                            {{ t('app.dashboard.this_month') }}
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(month_minutes + (isToday ? activeSessionMinutes : 0))" muted />
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon><LayersIcon /></StatItemIcon>
                            {{ t('app.dashboard.sessions') }}
                        </StatItemLabel>
                        <StatItemValue :value="String(session_stats.session_count + (activeSession ? 1 : 0))" muted />
                    </StatItem>

                    <template v-if="session_stats.avg_session_minutes > 0">
                        <Separator orientation="vertical" class="h-8 opacity-0" />
                        <StatItem>
                            <StatItemLabel>
                                <StatItemIcon><TimerIcon /></StatItemIcon>
                                {{ t('app.dashboard.avg_session') }}
                            </StatItemLabel>
                            <StatItemValue :value="formatMinutes(session_stats.avg_session_minutes)" muted />
                        </StatItem>
                    </template>

                    <template v-if="workRange">
                        <Separator orientation="vertical" class="h-8 opacity-0" />
                        <StatItem>
                            <StatItemLabel>
                                <StatItemIcon><TimerResetIcon /></StatItemIcon>
                                {{ t('app.dashboard.work_hours') }}
                            </StatItemLabel>
                            <StatItemValue :value="workRange" muted />
                        </StatItem>
                    </template>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black tracking-widest text-muted-foreground/60 uppercase">{{ dateLabel }}</p>
                </div>
            </StatsBar>

            <OnboardingChecklist v-if="showChecklist" :onboarding="onboarding" />

            <div v-if="sessions.length > 0 || (isToday && activeSessionMinutes > 0)" class="grid gap-10">
                <DaySummaryCard :sessions="sessions" :date="date" />

                <!-- Detail List Section -->
                <div class="space-y-6">
                    <div class="flex items-center gap-3 px-1">
                        <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">
                            {{ t('app.common.recent_activity') }}
                        </h3>
                        <Separator class="flex-1 opacity-20" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <SessionRow
                            v-if="activeSession"
                            :session="activeSession"
                            :show-date="false"
                            class="cursor-pointer"
                            @click="router.visit(sessionRoutes.show(activeSession).url)"
                        />
                        <SessionRow
                            v-for="session in sessions"
                            :key="session.id"
                            :session="session"
                            :show-date="false"
                            class="cursor-pointer"
                            @click="router.visit(sessionRoutes.show(session).url)"
                        />
                    </div>
                </div>
            </div>

            <Empty v-else>
                <EmptyTitle>{{ t('app.dashboard.no_activity_yet') }}</EmptyTitle>
                <EmptyDescription>{{ t('app.dashboard.no_activity_yet_description') }}</EmptyDescription>
                <div class="mt-4">
                    <Button size="sm" @click="logSessionOpen = true">{{ t('app.dashboard.log_session') }}</Button>
                </div>
            </Empty>
        </div>
    </AppLayout>

    <LogPastSessionSheet v-model:open="logSessionOpen" />
</template>
