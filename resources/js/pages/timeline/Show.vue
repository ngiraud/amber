<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, ClockIcon, RadioIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import DaySummaryCard from '@/components/DaySummaryCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import SessionRow from '@/components/SessionRow.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { useNow } from '@/composables/useNow';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { Session } from '@/types';

const props = defineProps<{
    date: string;
    previous_date: string;
    next_date: string;
    sessions: Session[];
    total_minutes: number;
}>();

const page = usePage();
const { shouldOpen } = useOpenSessionDialog();
const { now, isToday: isTodayFn } = useNow();

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

    return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
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
    <AppLayout :title="dateLabel" :breadcrumb="['Timeline', dateLabel]">
        <template #header>
            <PageHeader :title="dateLabel">
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="timelineRoutes.index().url">Timeline</Link>
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
                        <ReconstructDialog :date="date" :has-sessions="sessions.length > 0">
                            <Button variant="outline" size="sm">
                                <RefreshCwIcon class="mr-1.5 size-3.5" />
                                Reconstruct
                            </Button>
                        </ReconstructDialog>

                        <Button size="sm" @click="shouldOpen = true">Add Session</Button>

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
            <!-- Compact Quick Stats -->
            <div class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-border/5 ring-inset">
                <div class="flex items-center gap-10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <ClockIcon class="-mt-0.5 size-3 text-muted-foreground" />
                            <span class="text-[9px] font-black tracking-widest text-muted-foreground/80 uppercase">Total</span>
                        </div>
                        <div class="flex items-baseline gap-2.5">
                            <p class="font-mono text-2xl font-black tracking-tighter">
                                {{ formatMinutes(total_minutes + activeSessionMinutes) }}
                            </p>

                            <Badge v-if="activeSession" class="animate-pulse">
                                <RadioIcon class="size-3" />
                                <span class="text-[9px] font-black tracking-tighter tabular-nums"> LIVE </span>
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black tracking-widest text-muted-foreground/60 uppercase">{{ dateLabel }}</p>
                </div>
            </div>

            <div v-if="sessions.length > 0" class="grid gap-10">
                <DaySummaryCard :sessions="sessions" :date="date" />

                <!-- Detail List Section -->
                <div class="space-y-6 px-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">Activity Details</h3>
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
                <EmptyTitle>No sessions for this day.</EmptyTitle>
                <EmptyDescription>Add a manual session or reconstruct from activity.</EmptyDescription>

                <div class="mt-4 flex gap-4">
                    <ReconstructDialog :date="date" :has-sessions="false">
                        <Button variant="outline" size="sm">
                            <RefreshCwIcon class="mr-1.5 size-3.5" />
                            Reconstruct
                        </Button>
                    </ReconstructDialog>

                    <Button size="sm" @click="shouldOpen = true">Add Session</Button>
                </div>
            </Empty>
        </div>
    </AppLayout>
</template>
