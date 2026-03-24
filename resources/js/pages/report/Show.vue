<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BanknoteIcon, CalendarDaysIcon, ClockIcon, DownloadIcon, EllipsisIcon, RefreshCwIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import RegenerateSheet from '@/components/RegenerateSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Separator } from '@/components/ui/separator';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes, formatPeriod } from '@/lib/utils';
import * as clientRoutes from '@/routes/clients';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, ActivityReportProgressPayload, ActivityReportStatus, ActivityReportStep, AiSettings } from '@/types';

const props = defineProps<{
    report: ActivityReport;
    aiSettings: AiSettings;
    reportSteps: ActivityReportStep[];
    reportStatuses: ActivityReportStatus[];
}>();

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
    if (payload.reportId !== props.report.id) {
        return;
    }

    if (payload.step === 'completed' || payload.step === 'failed') {
        router.reload({ only: ['report', 'flash', 'error'] });
    } else {
        currentStep.value = payload.step;

        if (payload.step === 'summarizing' && payload.message) {
            router.flash('error', payload.message);
        }
    }

    regenerateSheetOpen.value = false;
});
</script>

<template>
    <AppLayout
        :title="`Report — ${formatPeriod(report.month, report.year)}`"
        :breadcrumb="[t('app.report.title'), report.client?.name ?? '', formatPeriod(report.month, report.year)].filter(Boolean)"
    >
        <template #header>
            <PageHeader :title="(report.client?.name ?? '') + ' — ' + formatPeriod(report.month, report.year)">
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="reportRoutes.index().url">{{ t('app.report.title') }}</Link>
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
                                <BreadcrumbPage>{{ formatPeriod(report.month, report.year) }}</BreadcrumbPage>
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
                            {{ t('app.report.regenerate') }}
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
                                    {{ t('app.common.delete') }}
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
                    <CardTitle>{{ t('app.report.generating_report') }}</CardTitle>
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
            <div class="flex flex-wrap items-center gap-8 rounded-xl border bg-card px-6 py-4 shadow-sm ring-1 ring-inset ring-border/5">
                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        {{ t('app.report.total_time') }}
                    </StatItemLabel>
                    <StatItemValue :value="formatMinutes(report.total_minutes)" />
                </StatItem>

                <Separator orientation="vertical" class="h-8 opacity-0" />

                <StatItem>
                    <StatItemLabel>
                        <StatItemIcon><CalendarDaysIcon /></StatItemIcon>
                        {{ t('app.report.total_days') }}
                    </StatItemLabel>
                    <StatItemValue :value="String(report.total_days)" muted />
                </StatItem>

                <template v-if="report.total_amount_ht !== null">
                    <Separator orientation="vertical" class="h-8 opacity-0" />
                    <StatItem>
                        <StatItemLabel>
                            <StatItemIcon><BanknoteIcon /></StatItemIcon>
                            {{ t('app.report.amount_ht') }}
                        </StatItemLabel>
                        <StatItemValue
                            :value="(report.total_amount_ht / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })"
                            muted
                        />
                    </StatItem>
                </template>
            </div>

            <!-- Notes -->
            <div v-if="report.notes" class="shrink-0">
                <h2 class="mb-1 text-sm font-semibold">{{ t('app.common.notes') }}</h2>
                <p class="text-sm text-muted-foreground">{{ report.notes }}</p>
            </div>

            <!-- Lines table -->
            <div v-if="report.lines && report.lines.length > 0" class="flex min-h-0 flex-1 flex-col">
                <h2 class="mb-3 shrink-0 text-base font-semibold">{{ t('app.report.activity_lines') }}</h2>
                <div class="min-h-0 flex-1 overflow-hidden rounded-lg border [&>[data-slot=table-container]]:h-full">
                    <Table>
                        <TableHeader class="sticky top-0 z-10 rounded-lg border bg-muted/50 backdrop-blur-sm">
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[100px]">{{ t('app.common.date') }}</TableHead>
                                <TableHead>{{ t('app.csv.project') }}</TableHead>
                                <TableHead class="text-right">{{ t('app.common.hours') }}</TableHead>
                                <TableHead class="text-right">{{ t('app.common.days') }}</TableHead>
                                <TableHead>{{ t('app.common.description') }}</TableHead>
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
