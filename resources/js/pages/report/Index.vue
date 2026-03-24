<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { FileTextIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import ReportSheet from '@/components/ReportSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useSpotlight } from '@/composables/useSpotlight';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes, formatPeriod } from '@/lib/utils';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, ActivityReportProgressPayload, AiSettings, Client } from '@/types';

const props = defineProps<{
    reports: ActivityReport[];
    clients: Client[];
    aiSettings: AiSettings;
}>();

const { spotlightClass } = useSpotlight();

useNativeEvent<ActivityReportProgressPayload>('App\\Events\\ActivityReportProgress', () => {
    router.reload({ only: ['reports'] });
});

type ClientGroup = {
    client: Client;
    reports: ActivityReport[];
};

const reportsByClient = computed<ClientGroup[]>(() => {
    const map = new Map<string, ClientGroup>();

    for (const report of props.reports) {
        if (!report.client) {
            continue;
        }

        if (!map.has(report.client_id)) {
            map.set(report.client_id, { client: report.client, reports: [] });
        }

        map.get(report.client_id)!.reports.push(report);
    }

    return [...map.values()];
});
</script>

<template>
    <AppLayout :title="t('app.report.title')">
        <template #header>
            <PageHeader :title="t('app.report.title')">
                <template #actions>
                    <ReportSheet :clients="clients" :ai-settings="aiSettings">
                        <Button size="sm" :class="spotlightClass('new-report')">{{ t('app.report.add') }}</Button>
                    </ReportSheet>
                </template>
            </PageHeader>
        </template>

        <Empty v-if="reports.length === 0" class="mt-6">
            <EmptyTitle>{{ t('app.report.no_reports') }}</EmptyTitle>
            <EmptyDescription>{{ t('app.report.no_reports_description_2') }}</EmptyDescription>
            <ReportSheet :clients="clients" :ai-settings="aiSettings">
                <Button size="sm">{{ t('app.report.add') }}</Button>
            </ReportSheet>
        </Empty>

        <div v-else class="flex flex-col gap-8">
            <div v-for="group in reportsByClient" :key="group.client.id">
                <div class="mb-3 flex items-center gap-3">
                    <h2 class="text-sm font-semibold">{{ group.client.name }}</h2>
                    <span class="text-xs text-muted-foreground">{{ group.reports.length }} {{ t('app.report.title').toLowerCase() }}</span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Link
                        v-for="report in group.reports"
                        :key="report.id"
                        :href="reportRoutes.show(report)"
                        class="group flex items-center justify-between rounded-lg border bg-card px-5 py-3.5 text-card-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <div class="flex items-center gap-3">
                            <FileTextIcon class="size-4 shrink-0 text-muted-foreground group-hover:text-accent-foreground/60" />
                            <span class="text-sm font-medium">{{ formatPeriod(report.month, report.year) }}</span>
                        </div>

                        <div class="flex items-center gap-4">
                            <span
                                v-if="report.status.value !== 5"
                                class="font-mono text-xs text-muted-foreground group-hover:text-accent-foreground/70"
                            >
                                {{ formatMinutes(report.total_minutes) }}
                                <span class="mx-1 opacity-40">·</span>
                                {{ report.total_days }}j
                                <template v-if="report.total_amount_ht !== null">
                                    <span class="mx-1 opacity-40">·</span>
                                    {{ (report.total_amount_ht / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}
                                </template>
                            </span>
                            <Badge :variant="report.status.variant" :class="report.status.value === 5 ? 'animate-pulse' : ''">
                                {{ report.status.label }}
                            </Badge>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
