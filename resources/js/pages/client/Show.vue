<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { CalendarDaysIcon, CalendarRangeIcon, ClockIcon, TimerIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ClientNotesDialog from '@/components/ClientNotesDialog.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import ClientSheet from '@/components/ClientSheet.vue';
import ProjectBreakdownItem from '@/components/ProjectBreakdownItem.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProjectSheet from '@/components/ProjectSheet.vue';
import StatsBar from '@/components/StatsBar.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useDateFormat } from '@/composables/useDateFormat';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import type { Client, ClientStats } from '@/types';

const props = defineProps<{
    client: Client;
    clients: Client[];
    client_stats: ClientStats;
}>();

const confirmDelete = ref(false);

const { formatDate } = useDateFormat();

const period = computed(() => {
    if (!props.client_stats.first_date || !props.client_stats.last_date) {
        return null;
    }

    return `${formatDate(props.client_stats.first_date)} → ${formatDate(props.client_stats.last_date)}`;
});
</script>

<template>
    <AppLayout :title="client.name" :breadcrumb="[t('app.client.title'), client.name]">
        <template #header>
            <PageHeader :title="client.name">
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="clientRoutes.index()">{{ t('app.client.title') }}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>{{ client.name }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </template>

                <template #actions>
                    <ClientSheet :client="client">
                        <Button variant="outline" size="sm">{{ t('app.common.edit') }}</Button>
                    </ClientSheet>

                    <Button variant="destructive" size="sm" @click="confirmDelete = true">{{ t('app.common.delete') }}</Button>

                    <Form :action="clientRoutes.destroy(client)" #default="{ submit }">
                        <ConfirmDialog
                            :open="confirmDelete"
                            :title="t('app.client.delete')"
                            :message="t('app.client.delete_confirm_message', { name: client.name })"
                            @confirm="submit"
                            @cancel="confirmDelete = false"
                        />
                    </Form>
                </template>
            </PageHeader>
        </template>

        <!-- Stats band — always visible -->
        <StatsBar v-if="client_stats.worked_days > 0" class="flex flex-wrap items-center gap-8">
            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><CalendarDaysIcon /></StatItemIcon>
                    {{ t('app.stats.worked_days') }}
                </StatItemLabel>
                <StatItemValue :value="String(client_stats.worked_days)" />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><ClockIcon /></StatItemIcon>
                    {{ t('app.stats.total_hours') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(client_stats.total_minutes)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><TimerIcon /></StatItemIcon>
                    {{ t('app.stats.avg_per_day') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(client_stats.avg_minutes_per_day)" muted />
            </StatItem>

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
        </StatsBar>

        <Tabs default-value="overview" class="mt-8">
            <TabsList>
                <TabsTrigger value="overview">{{ t('app.stats.overview') }}</TabsTrigger>
                <TabsTrigger value="notes">{{ t('app.common.notes') }}</TabsTrigger>
                <TabsTrigger value="activity">{{ t('app.stats.activity') }}</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" class="mt-4 space-y-4">
                <!-- Project breakdown -->
                <Card v-if="client_stats.project_breakdown.length > 0">
                    <CardHeader>
                        <CardTitle>{{ t('app.stats.breakdown') }}</CardTitle>
                        <CardDescription>{{ t('app.stats.breakdown_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <ProjectBreakdownItem
                                v-for="project in client_stats.project_breakdown"
                                :key="project.id"
                                :name="project.name"
                                :color="project.color"
                                :minutes="project.minutes"
                                :percentage="project.percentage"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Projects list -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>{{ t('app.client.projects') }}</CardTitle>
                                <CardDescription class="mt-1">{{ t('app.stats.projects_description') }}</CardDescription>
                            </div>
                            <ProjectSheet :client="client" :clients="clients">
                                <Button size="sm">{{ t('app.client.add_project') }}</Button>
                            </ProjectSheet>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Empty v-if="!client.projects?.length">
                            <EmptyTitle>{{ t('app.project.no_projects') }}</EmptyTitle>
                            <EmptyDescription>{{ t('app.client.no_projects_description_client') }}</EmptyDescription>
                            <ProjectSheet :client="client" :clients="clients">
                                <Button size="sm">{{ t('app.client.add_project') }}</Button>
                            </ProjectSheet>
                        </Empty>

                        <div v-else class="grid grid-cols-2 gap-3">
                            <Link
                                v-for="project in client.projects"
                                :key="project.id"
                                :href="projectRoutes.show(project)"
                                class="group flex flex-col gap-2 rounded-lg border bg-card p-4 text-card-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                                    <span class="text-sm font-medium">{{ project.name }}</span>
                                    <Badge v-if="!project.is_active" variant="secondary" class="ml-auto text-xs">
                                        {{ t('app.common.inactive') }}
                                    </Badge>
                                </div>

                                <div class="flex gap-4 text-xs text-muted-foreground group-hover:text-accent-foreground/70">
                                    <span v-if="project.daily_rate_formatted">
                                        <span class="font-medium">{{ project.daily_rate_formatted }}</span
                                        >{{ t('app.common.per_day') }}
                                    </span>
                                    <span v-if="project.hourly_rate_formatted">
                                        <span class="font-medium">{{ project.hourly_rate_formatted }}</span
                                        >{{ t('app.common.per_hr') }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="notes" class="mt-4">
                <Empty v-if="!client.notes">
                    <EmptyTitle>{{ t('app.client.no_notes') }}</EmptyTitle>
                    <EmptyDescription>{{ t('app.client.no_notes_description') }}</EmptyDescription>
                    <ClientNotesDialog :client="client">
                        <Button size="sm" class="mt-4">{{ t('app.client.add_notes') }}</Button>
                    </ClientNotesDialog>
                </Empty>

                <template v-else>
                    <RichTextEditor :model-value="client.notes" :editable="false" />

                    <div class="mt-4 flex justify-end">
                        <ClientNotesDialog :client="client">
                            <Button variant="outline" size="sm">{{ t('app.client.edit_notes') }}</Button>
                        </ClientNotesDialog>
                    </div>
                </template>
            </TabsContent>

            <TabsContent value="activity" class="mt-4">
                <div class="h-125">
                    <ActivityLog />
                </div>
            </TabsContent>
        </Tabs>
    </AppLayout>
</template>
