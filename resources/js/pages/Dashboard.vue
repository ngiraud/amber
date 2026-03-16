<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDaysIcon, ClockIcon, LayersIcon, RadioIcon, RefreshCwIcon, TargetIcon, TimerIcon, TimerResetIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useDateFormat } from '@/composables/useDateFormat';
import DaySummaryCard from '@/components/DaySummaryCard.vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import SessionRow from '@/components/SessionRow.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { StatItem, StatItemLabel, StatItemValue } from '@/components/stat';
import { useNow } from '@/composables/useNow';
import LogPastSessionSheet from '@/components/LogPastSessionSheet.vue';
import { useSpotlight } from '@/composables/useSpotlight';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { OnboardingState, Session } from '@/types';

const props = defineProps<{
    date: string;
    sessions: Session[];
    total_minutes: number;
    week_minutes: number;
    month_minutes: number;
    session_count: number;
    avg_session_minutes: number;
    first_started_at: string | null;
    last_ended_at: string | null;
}>();

const page = usePage();
const onboarding = computed(() => page.props.onboarding as OnboardingState);
const showChecklist = computed(() => !onboarding.value?.dismissed && !onboarding.value?.all_complete);

const { spotlightClass } = useSpotlight();
const logSessionOpen = ref(false);
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

    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
});

const workRange = computed(() => {
    if (!props.first_started_at || !props.last_ended_at) {
        return null;
    }

    return `${formatTime(props.first_started_at)} → ${formatTime(props.last_ended_at)}`;
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

                        <ReconstructDialog :has-sessions="sessions.length > 0">
                            <Button variant="outline" size="sm">
                                <RefreshCwIcon class="mr-1.5 size-3.5" />
                                Reconstruct today
                            </Button>
                        </ReconstructDialog>

                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="flex flex-col gap-10">
            <!-- Compact Quick Stats -->
            <div class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-border/5 ring-inset">
                <div class="flex items-center gap-10">
                    <StatItem>
                        <StatItemLabel>
                            <template #icon><ClockIcon class="-mt-0.5 size-3 text-muted-foreground" /></template>
                            Today
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(total_minutes + activeSessionMinutes)" :active="!!activeSession">
                            <Badge v-if="activeSession" class="animate-pulse">
                                <RadioIcon class="size-3" />
                                <span class="text-[9px] font-black tracking-tighter tabular-nums"> LIVE </span>
                            </Badge>
                        </StatItemValue>
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <template #icon><TargetIcon class="-mt-px size-3 text-muted-foreground" /></template>
                            This Week
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(week_minutes + (isToday ? activeSessionMinutes : 0))" muted />
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <template #icon><CalendarDaysIcon class="-mt-0.5 size-3 text-muted-foreground" /></template>
                            This Month
                        </StatItemLabel>
                        <StatItemValue :value="formatMinutes(month_minutes + (isToday ? activeSessionMinutes : 0))" muted />
                    </StatItem>

                    <Separator orientation="vertical" class="h-8 opacity-0" />

                    <StatItem>
                        <StatItemLabel>
                            <template #icon><LayersIcon class="-mt-0.5 size-3 text-muted-foreground" /></template>
                            Sessions
                        </StatItemLabel>
                        <StatItemValue :value="String(session_count + (activeSession ? 1 : 0))" muted />
                    </StatItem>

                    <template v-if="avg_session_minutes > 0">
                        <Separator orientation="vertical" class="h-8 opacity-0" />
                        <StatItem>
                            <StatItemLabel>
                                <template #icon><TimerIcon class="-mt-0.5 size-3 text-muted-foreground" /></template>
                                Avg session
                            </StatItemLabel>
                            <StatItemValue :value="formatMinutes(avg_session_minutes)" muted />
                        </StatItem>
                    </template>

                    <template v-if="workRange">
                        <Separator orientation="vertical" class="h-8 opacity-0" />
                        <StatItem>
                            <StatItemLabel>
                                <template #icon><TimerResetIcon class="-mt-0.5 size-3 text-muted-foreground" /></template>
                                Work hours
                            </StatItemLabel>
                            <StatItemValue :value="workRange" muted />
                        </StatItem>
                    </template>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black tracking-widest text-muted-foreground/60 uppercase">{{ dateLabel }}</p>
                </div>
            </div>

            <OnboardingChecklist v-if="showChecklist" :onboarding="onboarding" />

            <div v-if="sessions.length > 0 || (isToday && activeSessionMinutes > 0)" class="grid gap-10">
                <DaySummaryCard :sessions="sessions" :date="date" />

                <!-- Detail List Section -->
                <div class="space-y-6">
                    <div class="flex items-center gap-3 px-1">
                        <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">Recent Activity</h3>
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
                <EmptyTitle>No activity recorded yet.</EmptyTitle>
                <EmptyDescription>Start tracking your work by adding a session.</EmptyDescription>
                <div class="mt-4">
                    <Button size="sm" @click="logSessionOpen = true">Log session</Button>
                </div>
            </Empty>
        </div>
    </AppLayout>

    <LogPastSessionSheet v-model:open="logSessionOpen" />
</template>
