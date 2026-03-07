<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { DownloadIcon, RefreshCwIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useNativeEvent } from '@/composables/useNativeEvent';
import AppLayout from '@/layouts/AppLayout.vue';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, ActivityReportProgressPayload, ActivityReportStep } from '@/types';

const props = defineProps<{
    report: ActivityReport;
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
    if (status.label === 'Generating') return 'outline';
    if (status.label === 'Draft') return 'secondary';
    if (status.label === 'Failed') return 'destructive';
    return 'default';
}

const STEPS: { key: ActivityReportStep; label: string }[] = [
    { key: 'collecting_context', label: 'Collecting context' },
    { key: 'building_lines', label: 'Building lines' },
    { key: 'generating_files', label: 'Generating files' },
    { key: 'completed', label: 'Done' },
];

const currentStep = ref<ActivityReportStep | null>(props.report.status.label === 'Generating' ? 'collecting_context' : null);

const isDeleting = ref(false);
const isRegenerating = ref(false);

function handleDelete(): void {
    router.delete(reportRoutes.destroy(props.report.id), {
        onStart: () => {
            isDeleting.value = true;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

function handleRegenerate(): void {
    router.post(
        reportRoutes.regenerate(props.report.id),
        {},
        {
            onStart: () => {
                isRegenerating.value = true;
            },
            onFinish: () => {
                isRegenerating.value = false;
            },
        },
    );
}

useNativeEvent<ActivityReportProgressPayload>('App\\Events\\ActivityReportProgress', (payload) => {
    if (payload.reportId !== props.report.id) return;
    if (payload.step === 'completed' || payload.step === 'failed') {
        router.reload({ only: ['report'] });
    } else {
        currentStep.value = payload.step;
    }
});
</script>

<template>
    <AppLayout :title="`Report — ${formatPeriod(report)}`">
        <template #header>
            <PageHeader :title="(report.client?.name ?? '') + ' — ' + formatPeriod(report)">
                <template #actions>
                    <Badge :variant="statusVariant(report.status)" :class="report.status.label === 'Generating' ? 'animate-pulse' : ''">
                        {{ report.status.label }}
                    </Badge>
                </template>
            </PageHeader>
        </template>

        <!-- Generating state -->
        <div v-if="report.status.label === 'Generating'">
            <Card>
                <CardHeader>
                    <CardTitle>Generating report…</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2">
                    <div
                        v-for="step in STEPS"
                        :key="step.key"
                        class="flex items-center gap-2 text-sm"
                        :class="{
                            'font-medium text-foreground': currentStep === step.key,
                            'text-muted-foreground': currentStep !== step.key,
                        }"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="{
                                'animate-pulse bg-primary': currentStep === step.key,
                                'bg-muted': currentStep !== step.key,
                            }"
                        />
                        {{ step.label }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Draft / ready state -->
        <div v-else class="flex flex-col gap-6">
            <!-- Totals -->
            <div class="grid grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total time</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-mono text-2xl font-semibold">{{ formatMinutes(report.total_minutes) }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-mono text-2xl font-semibold">{{ report.total_days }}</p>
                    </CardContent>
                </Card>
                <Card v-if="report.total_amount_ht !== null">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Amount (HT)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-mono text-2xl font-semibold">
                            {{ (report.total_amount_ht / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Lines table -->
            <div v-if="report.lines && report.lines.length > 0">
                <h2 class="mb-3 text-base font-semibold">Activity lines</h2>
                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Date</th>
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Project</th>
                                <th class="px-4 py-2 text-right font-medium text-muted-foreground">Hours</th>
                                <th class="px-4 py-2 text-right font-medium text-muted-foreground">Days</th>
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in report.lines" :key="line.id" class="border-t transition-colors hover:bg-muted/30">
                                <td class="px-4 py-2 font-mono text-xs text-muted-foreground">{{ line.date }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="line.project?.color"
                                            class="size-2 shrink-0 rounded-full"
                                            :style="{ backgroundColor: line.project.color }"
                                        />
                                        {{ line.project?.name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right font-mono">{{ (line.minutes / 60).toFixed(2) }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ line.days.toFixed(2) }}</td>
                                <td class="px-4 py-2 text-xs text-muted-foreground">{{ line.description ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="report.notes">
                <h2 class="mb-1 text-sm font-semibold">Notes</h2>
                <p class="text-sm text-muted-foreground">{{ report.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 border-t pt-4">
                <Button v-if="report.pdf_path" size="sm" variant="outline" as-child>
                    <a :href="reportRoutes.exportMethod({ report: report.id, format: 'pdf' }).url">
                        <DownloadIcon class="mr-1.5 size-3.5" />
                        PDF
                    </a>
                </Button>
                <Button v-if="report.csv_path" size="sm" variant="outline" as-child>
                    <a :href="reportRoutes.exportMethod({ report: report.id, format: 'csv' }).url">
                        <DownloadIcon class="mr-1.5 size-3.5" />
                        CSV
                    </a>
                </Button>

                <div class="ml-auto flex items-center gap-2">
                    <Button size="sm" variant="outline" :disabled="isRegenerating" @click="handleRegenerate">
                        <RefreshCwIcon class="mr-1.5 size-3.5" />
                        Regenerate
                    </Button>
                    <Button size="sm" variant="destructive" :disabled="isDeleting" @click="handleDelete">
                        <Trash2Icon class="mr-1.5 size-3.5" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
