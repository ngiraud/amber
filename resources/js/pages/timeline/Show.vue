<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import ReconstructDialog from '@/components/ReconstructDialog.vue';
import SessionRow from '@/components/SessionRow.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import AppLayout from '@/layouts/AppLayout.vue';
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

const { shouldOpen } = useOpenSessionDialog();

const fromDateDialog = ref<InstanceType<typeof ReconstructDialog> | null>(null);

const dateLabel = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const formattedTotal = computed(() => {
    const h = Math.floor(props.total_minutes / 60);
    const m = props.total_minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
});

function navigate(direction: -1 | 1): void {
    const date = direction === -1 ? props.previous_date : props.next_date;
    router.get(timelineRoutes.show({ date }).url);
}
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

        <Empty v-if="sessions.length === 0" class="mt-4">
            <EmptyTitle>No sessions for this day.</EmptyTitle>
            <EmptyDescription>Add a manual session or reconstruct from activity.</EmptyDescription>

            <div class="flex gap-4">
                <ReconstructDialog :date="date" :has-sessions="false">
                    <Button variant="outline" size="sm">
                        <RefreshCwIcon class="mr-1.5 size-3.5" />
                        Reconstruct
                    </Button>
                </ReconstructDialog>

                <Button size="sm" @click="shouldOpen = true">Add Session</Button>
            </div>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <SessionRow
                v-for="session in sessions"
                :key="session.id"
                :session="session"
                class="cursor-pointer"
                @click="router.visit(sessionRoutes.show(session).url)"
            />

            <div class="mt-3 flex justify-end border-t pt-3">
                <p class="text-sm font-medium">
                    Total: <span class="font-mono">{{ formattedTotal }}</span>
                </p>
            </div>
        </div>

        <ReconstructDialog ref="fromDateDialog" batch />
    </AppLayout>
</template>
