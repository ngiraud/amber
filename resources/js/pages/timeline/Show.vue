<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    LayersIcon,
    RadioIcon,
    RefreshCwIcon,
    TimerIcon,
    TimerResetIcon,
    CalendarDaysIcon,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import DaySummaryCard from '@/components/DaySummaryCard.vue';
import LogPastSessionSheet from '@/components/LogPastSessionSheet.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import SessionRow from '@/components/SessionRow.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import StatsBar from '@/components/StatsBar.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
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
import type { Session, SessionStats } from '@/types';

const props = defineProps<{
    date: string;
    previous_date: string;
    next_date: string;
    sessions: Session[];
    session_stats: SessionStats;
}>();

const page = usePage();
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
    const str = d.toLocaleDateString(locale.value, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    return str.charAt(0).toUpperCase() + str.slice(1);
});

const timelineMonthUrl = computed(() => {
    const [year, month] = props.date.split('-').map(Number);

    return timelineRoutes.index({ query: { year, month } }).url;
});

const workRange = computed(() => {
    if (!props.session_stats.first_started_at || !props.session_stats.last_ended_at) {
        return null;
    }

    return `${formatTime(props.session_stats.first_started_at)} → ${formatTime(props.session_stats.last_ended_at)}`;
});

function navigate(direction: -1 | 1): void {
    const date = direction === -1 ? props.previous_date : props.next_date;
    router.get(timelineRoutes.show({ date }).url);
}

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

onMounted(() => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => window.removeEventListener('keydown', onKeyDown));
</script>

<template>
    <AppLayout :title="dateLabel" :breadcrumb="[t('app.nav.timeline'), dateLabel]">
        <template #header>
            <PageHeader :title="dateLabel">
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="timelineMonthUrl">{{ t('app.nav.timeline') }}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>{{ dateLabel }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </template>
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="timelineMonthUrl">
                                <CalendarDaysIcon class="mr-1.5 size-3.5" />
                                {{ t('app.nav.timeline') }}
                            </Link>
                        </Button>

                        <ReconstructDialog :date="date" :has-sessions="sessions.length > 0">
                            <Button variant="outline" size="sm">
                                <RefreshCwIcon class="mr-1.5 size-3.5" />
                                {{ t('app.timeline.reconstruct') }}
                            </Button>
                        </ReconstructDialog>

                        <Button size="sm" @click="logSessionOpen = true">{{ t('app.dashboard.log_session') }}</Button>

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

        <div class="flex flex-col gap-10">
            <StatsBar class="flex items-center gap-10">
                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        {{ t('app.common.total') }}
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
            </StatsBar>

            <div v-if="sessions.length > 0" class="grid gap-10">
                <DaySummaryCard :sessions="sessions" :date="date" />

                <div class="space-y-6 px-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">
                            {{ t('app.timeline.activity_details') }}
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
                <EmptyTitle>{{ t('app.timeline.no_sessions_for_day') }}</EmptyTitle>
                <EmptyDescription>{{ t('app.timeline.no_sessions_description') }}</EmptyDescription>

                <div class="mt-4 flex gap-4">
                    <ReconstructDialog :date="date" :has-sessions="false">
                        <Button variant="outline" size="sm">
                            <RefreshCwIcon class="mr-1.5 size-3.5" />
                            {{ t('app.timeline.reconstruct') }}
                        </Button>
                    </ReconstructDialog>

                    <Button size="sm" @click="logSessionOpen = true">{{ t('app.dashboard.log_session') }}</Button>
                </div>
            </Empty>
        </div>
    </AppLayout>

    <LogPastSessionSheet v-model:open="logSessionOpen" :date="date" />
</template>
