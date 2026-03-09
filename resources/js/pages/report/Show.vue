<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { DownloadIcon, EllipsisIcon, RefreshCwIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import RegenerateSheet from '@/components/RegenerateSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useNativeEvent } from '@/composables/useNativeEvent';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, ActivityReportProgressPayload, ActivityReportStatus, ActivityReportStep, AiSettings } from '@/types';

const props = defineProps<{
    report: ActivityReport;
    aiSettings: AiSettings;
    reportSteps: ActivityReportStep[];
    reportStatuses: ActivityReportStatus[];
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

const currentStep = ref<string | null>(props.report.status.label === 'Generating' ? props.reportSteps[0].value : null);

const isDeleting = ref(false);
const regenerateSheetOpen = ref(false);

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

useNativeEvent<ActivityReportProgressPayload>('App\\Events\\ActivityReportProgress', (payload) => {
    if (payload.reportId !== props.report.id) return;
    if (payload.step === 'completed' || payload.step === 'failed') {
        router.reload({ only: ['report'] });
    } else {
        currentStep.value = payload.step;
    }

    regenerateSheetOpen.value = false;
});
</script>

<template>
    <AppLayout :title="`Report — ${formatPeriod(report)}`" :breadcrumb="['Reports', report.client?.name ?? '', formatPeriod(report)].filter(Boolean)">
        <template #header>
            <PageHeader :title="(report.client?.name ?? '') + ' — ' + formatPeriod(report)">
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="reportRoutes.index().url">Reports</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem v-if="report.client?.name">
                                <BreadcrumbLink as-child>
                                    <Link :href="clientRoutes.show(report.client)">{{ report.client.name }}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>{{ formatPeriod(report) }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </template>
                <template #actions>
                    <Badge
                        v-if="report.status.shouldDisplayBadge"
                        :variant="report.status.variant"
                        :class="report.status.label === 'Generating' ? 'animate-pulse' : ''"
                    >
                        {{ report.status.label }}
                    </Badge>
                    <div v-if="report.status.label !== 'Generating'" class="flex items-center gap-2">
                        <Button v-if="report.pdf_path" size="sm" as-child>
                            <a :href="reportRoutes.exportMethod({ report: report.id, format: 'pdf' }).url">
                                <DownloadIcon class="mr-2 size-3.5" />
                                PDF
                            </a>
                        </Button>
                        <Button v-if="report.csv_path" size="sm" as-child>
                            <a :href="reportRoutes.exportMethod({ report: report.id, format: 'csv' }).url">
                                <DownloadIcon class="mr-2 size-3.5" />
                                CSV
                            </a>
                        </Button>
                        <RegenerateSheet v-model:open="regenerateSheetOpen" :report="report" :ai-settings="aiSettings" />
                        <Button size="sm" variant="outline" @click="regenerateSheetOpen = true">
                            <RefreshCwIcon class="mr-2 size-3.5" />
                            Regenerate
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button size="sm" variant="outline">
                                    <EllipsisIcon class="size-3.5" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem variant="destructive" :disabled="isDeleting" @click="handleDelete">
                                    <Trash2Icon class="mr-2 size-3.5" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
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
                        v-for="step in props.reportSteps"
                        :key="step.value"
                        class="flex items-center gap-2 text-sm"
                        :class="{
                            'font-medium text-foreground': currentStep === step.value,
                            'text-muted-foreground': currentStep !== step.value,
                        }"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="{
                                'animate-pulse bg-primary': currentStep === step.value,
                                'bg-muted': currentStep !== step.value,
                            }"
                        />
                        {{ step.label }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Draft / ready state -->
        <div v-else class="flex min-h-0 flex-1 flex-col gap-6">
            <!-- Totals -->
            <div class="grid shrink-0 grid-cols-3 gap-4">
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

            <!-- Notes -->
            <div v-if="report.notes" class="shrink-0">
                <h2 class="mb-1 text-sm font-semibold">Notes</h2>
                <p class="text-sm text-muted-foreground">{{ report.notes }}</p>
            </div>

            <!-- Lines table -->
            <div v-if="report.lines && report.lines.length > 0" class="flex min-h-0 flex-1 flex-col">
                <h2 class="mb-3 shrink-0 text-base font-semibold">Activity lines</h2>
                <div class="min-h-0 flex-1 overflow-hidden rounded-lg border [&>[data-slot=table-container]]:h-full">
                    <Table>
                        <TableHeader class="sticky top-0 z-10 rounded-lg border bg-muted/50 backdrop-blur-sm">
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[100px]">Date</TableHead>
                                <TableHead>Project</TableHead>
                                <TableHead class="text-right">Hours</TableHead>
                                <TableHead class="text-right">Days</TableHead>
                                <TableHead>Description</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="line in report.lines" :key="line.id">
                                <TableCell class="font-mono font-medium">{{ line.date }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="line.project?.color"
                                            class="size-2 shrink-0 rounded-full"
                                            :style="{ backgroundColor: line.project.color }"
                                        />
                                        {{ line.project?.name ?? '—' }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right font-mono">{{ (line.minutes / 60).toFixed(2) }}</TableCell>
                                <TableCell class="text-right font-mono">{{ line.days.toFixed(2) }}</TableCell>
                                <TableCell class="whitespace-normal">{{ line.display_description ?? '—' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
