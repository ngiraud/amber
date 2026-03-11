<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDaysIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionRow from '@/components/SessionRow.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import { useSpotlight } from '@/composables/useSpotlight';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import * as timelineRoutes from '@/routes/timeline';
import type { OnboardingState, Session } from '@/types';

const props = defineProps<{
    date: string;
    sessions: Session[];
    total_minutes: number;
    week_minutes: number;
    month_minutes: number;
}>();

const page = usePage<{ onboarding: OnboardingState }>();
const onboarding = computed(() => page.props.onboarding);
const showChecklist = computed(() => !onboarding.value?.dismissed && !onboarding.value?.all_complete);

const { spotlightClass } = useSpotlight();

const { shouldOpen } = useOpenSessionDialog();

function formatMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (minutes === 0) return '0h';
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

const dateLabel = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
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

                        <Button
                            size="sm"
                            :class="spotlightClass('start-session')"
                            @click="shouldOpen = true"
                        >
                            Add Session
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <OnboardingChecklist v-if="showChecklist" :onboarding="onboarding" />

        <div class="mb-6 grid grid-cols-3 gap-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Today</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(total_minutes) }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ dateLabel }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">This Week</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(week_minutes) }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">This Month</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-mono text-2xl font-semibold">{{ formatMinutes(month_minutes) }}</p>
                </CardContent>
            </Card>
        </div>

        <Empty v-if="sessions.length === 0" class="mt-4">
            <EmptyDescription>No sessions today yet.</EmptyDescription>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <SessionRow
                v-for="session in sessions"
                :key="session.id"
                :session="session"
                :show-date="true"
                @click="router.visit(sessionRoutes.show(session).url)"
                class="cursor-pointer"
            />
        </div>
    </AppLayout>
</template>
