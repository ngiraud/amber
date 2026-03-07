<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { FileTextIcon } from 'lucide-vue-next';
import PageHeader from '@/components/PageHeader.vue';
import ReportSheet from '@/components/ReportSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { useNativeEvent } from '@/composables/useNativeEvent';
import AppLayout from '@/layouts/AppLayout.vue';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, ActivityReportProgressPayload, Client, Paginator } from '@/types';

defineProps<{
    reports: Paginator<ActivityReport>;
    clients: Client[];
}>();

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function formatPeriod(report: ActivityReport): string {
    return `${MONTHS[report.month - 1]} ${report.year}`;
}

function formatMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

function statusVariant(status: ActivityReport['status']): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status.value === 5) return 'outline'; // Generating
    if (status.value === 10) return 'secondary'; // Draft
    if (status.value === 20) return 'default'; // Finalized
    return 'default'; // Sent
}

useNativeEvent<ActivityReportProgressPayload>('App\\Events\\ActivityReportProgress', () => {
    router.reload({ only: ['reports'] });
});
</script>

<template>
    <AppLayout title="Reports">
        <template #header>
            <PageHeader title="Reports">
                <template #actions>
                    <ReportSheet :clients="clients">
                        <Button size="sm">New report</Button>
                    </ReportSheet>
                </template>
            </PageHeader>
        </template>

        <Empty v-if="reports.data.length === 0" class="mt-6">
            <EmptyTitle>No reports yet</EmptyTitle>
            <EmptyDescription>Generate your first CRA by clicking the button above.</EmptyDescription>
            <ReportSheet :clients="clients">
                <Button size="sm">New report</Button>
            </ReportSheet>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <Link
                v-for="report in reports.data"
                :key="report.id"
                :href="reportRoutes.show(report)"
                class="flex items-center justify-between rounded-lg border bg-card px-5 py-4 text-card-foreground transition-colors hover:bg-accent"
            >
                <div class="flex items-center gap-3">
                    <FileTextIcon class="size-4 shrink-0 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">{{ report.client?.name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ formatPeriod(report) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="report.status.value !== 5" class="text-xs text-muted-foreground">
                        {{ formatMinutes(report.total_minutes) }} · {{ report.total_days }}j
                    </span>
                    <Badge :variant="statusVariant(report.status)" :class="report.status.value === 5 ? 'animate-pulse' : ''">
                        {{ report.status.label }}
                    </Badge>
                </div>
            </Link>

            <div v-if="reports.last_page > 1" class="mt-4 flex items-center justify-between">
                <Button v-if="reports.prev_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="reports.prev_page_url">← Previous</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">← Previous</span>

                <span class="text-xs text-muted-foreground">Page {{ reports.current_page }} of {{ reports.last_page }}</span>

                <Button v-if="reports.next_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="reports.next_page_url">Next →</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
