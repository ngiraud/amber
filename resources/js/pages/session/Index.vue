<script setup lang="ts">
import { Deferred, InfiniteScroll, router } from '@inertiajs/vue3';
import { CalendarRangeIcon, ClockIcon, LayersIcon, TimerIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import LogPastSessionSheet from '@/components/LogPastSessionSheet.vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionRow from '@/components/SessionRow.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Button } from '@/components/ui/button';
import { Empty, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { useDateFormat } from '@/composables/useDateFormat';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
import type { Paginator, Session, SessionStats } from '@/types';

const props = defineProps<{
    sessions: Paginator<Session>;
    session_stats: SessionStats | null;
}>();

const logSessionOpen = ref(false);

const { formatDate } = useDateFormat();

const period = computed(() => {
    if (!props.session_stats?.first_started_at || !props.session_stats?.last_ended_at) {
        return null;
    }

    return `${formatDate(props.session_stats.first_started_at)} → ${formatDate(props.session_stats.last_ended_at)}`;
});
</script>

<template>
    <AppLayout :title="t('app.nav.sessions')">
        <template #header>
            <PageHeader :title="t('app.nav.sessions')">
                <template #actions>
                    <Button size="sm" @click="logSessionOpen = true">{{ t('app.dashboard.log_session') }}</Button>
                </template>
            </PageHeader>
        </template>

        <Deferred data="session_stats">
            <template #fallback>
                <div class="mb-6 flex flex-wrap items-center gap-8 rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-border/5 ring-inset">
                    <StatItem
                        v-for="label in [
                            t('app.stats.total_hours'),
                            t('app.dashboard.sessions'),
                            t('app.dashboard.avg_session'),
                            t('app.stats.period'),
                        ]"
                        :key="label"
                        class="min-w-40"
                    >
                        <StatItemLabel>{{ label }}</StatItemLabel>
                        <Skeleton class="h-8 w-16" />
                    </StatItem>
                </div>
            </template>

            <div
                v-if="session_stats && session_stats.session_count > 0"
                class="mb-6 flex flex-wrap items-center gap-8 rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-border/5 ring-inset"
            >
                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        {{ t('app.stats.total_hours') }}
                    </StatItemLabel>
                    <StatItemValue :value="formatMinutes(session_stats.total_minutes)" />
                </StatItem>

                <Separator orientation="vertical" class="h-8 opacity-0" />

                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><LayersIcon /></StatItemIcon>
                        {{ t('app.dashboard.sessions') }}
                    </StatItemLabel>
                    <StatItemValue :value="String(session_stats.session_count)" muted />
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

                <template v-if="period">
                    <Separator orientation="vertical" class="h-8 opacity-0" />
                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon><CalendarRangeIcon /></StatItemIcon>
                            {{ t('app.stats.period') }}
                        </StatItemLabel>
                        <StatItemValue :value="period" muted />
                    </StatItem>
                </template>
            </div>
        </Deferred>

        <Empty v-if="(sessions?.data || []).length === 0" class="mt-6">
            <EmptyTitle>{{ t('app.session.no_sessions') }}</EmptyTitle>
        </Empty>

        <InfiniteScroll v-else data="sessions" :buffer="200">
            <template #loading>
                <div class="mt-1.5 h-[58px] animate-pulse rounded-lg border bg-card" />
            </template>

            <div class="flex flex-col gap-1.5">
                <SessionRow
                    v-for="session in sessions.data"
                    :key="session.id"
                    :session="session"
                    :show-date="true"
                    class="cursor-pointer"
                    @click="router.visit(sessionRoutes.show(session).url)"
                />
            </div>
        </InfiniteScroll>
    </AppLayout>

    <LogPastSessionSheet v-model:open="logSessionOpen" />
</template>
