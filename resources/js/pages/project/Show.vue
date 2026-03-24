<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { CalendarDaysIcon, CalendarRangeIcon, ClockIcon, TimerIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProjectSheet from '@/components/ProjectSheet.vue';
import RepositorySheet from '@/components/RepositorySheet.vue';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import StatsBar from '@/components/StatsBar.vue';
import ToggleProjectStatusDialog from '@/components/ToggleProjectStatusDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemTitle } from '@/components/ui/item';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useDateFormat } from '@/composables/useDateFormat';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMinutes } from '@/lib/utils';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import repositories from '@/routes/projects/repositories';
import type { Client, Project, ProjectRepository, ProjectStats } from '@/types';

const props = defineProps<{
    client: Client;
    project: Project;
    clients: Client[];
    project_stats: ProjectStats;
}>();

const repoToDelete = ref<ProjectRepository | null>(null);
const confirmDelete = ref(false);

const { formatDate } = useDateFormat();

const period = computed(() => {
    if (!props.project_stats.first_date || !props.project_stats.last_date) {
        return null;
    }

    return `${formatDate(props.project_stats.first_date)} → ${formatDate(props.project_stats.last_date)}`;
});

function removeRepo(): void {
    if (!repoToDelete.value) {
        return;
    }

    router.delete(repositories.destroy({ project: props.project, repository: repoToDelete.value }).url, {
        onSuccess: () => {
            repoToDelete.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :title="project.name" :breadcrumb="[t('app.client.title'), client.name, project.name]">
        <template #header>
            <PageHeader>
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
                                <BreadcrumbLink as-child>
                                    <Link :href="clientRoutes.show(client)">{{ client.name }}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>{{ project.name }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </template>

                <template #title>
                    <div class="flex items-center gap-3">
                        <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: project.color }" />
                        <h1 class="text-xl font-semibold">{{ project.name }}</h1>
                        <Badge v-if="!project.is_active" variant="secondary">{{ t('app.common.inactive') }}</Badge>
                    </div>
                </template>

                <template #actions>
                    <ProjectSheet :project="project" :clients="clients">
                        <Button variant="outline" size="sm">{{ t('app.common.edit') }}</Button>
                    </ProjectSheet>

                    <ToggleProjectStatusDialog :project="project">
                        <Button variant="outline" size="sm">
                            {{ project.is_active ? t('app.common.archive') : t('app.common.restore') }}
                        </Button>
                    </ToggleProjectStatusDialog>

                    <Button variant="destructive" size="sm" @click="confirmDelete = true">{{ t('app.common.delete') }}</Button>

                    <Form :action="projectRoutes.destroy(project)" #default="{ submit }">
                        <ConfirmDialog
                            :open="confirmDelete"
                            :title="t('app.project.delete')"
                            :message="t('app.project.delete_confirm_message', { name: project.name })"
                            @confirm="submit"
                            @cancel="confirmDelete = false"
                        />
                    </Form>
                </template>
            </PageHeader>
        </template>

        <!-- Stats band — always visible -->
        <StatsBar v-if="project_stats.worked_days > 0" class="flex flex-wrap items-center gap-8">
            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><CalendarDaysIcon /></StatItemIcon>
                    {{ t('app.stats.worked_days') }}
                </StatItemLabel>
                <StatItemValue :value="String(project_stats.worked_days)" />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><ClockIcon /></StatItemIcon>
                    {{ t('app.stats.total_hours') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(project_stats.total_minutes)" muted />
            </StatItem>

            <Separator orientation="vertical" class="h-8 opacity-0" />

            <StatItem>
                <StatItemLabel>
                    <StatItemIcon><TimerIcon /></StatItemIcon>
                    {{ t('app.stats.avg_per_day') }}
                </StatItemLabel>
                <StatItemValue :value="formatMinutes(project_stats.avg_minutes_per_day)" muted />
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
                <TabsTrigger value="activity">{{ t('app.stats.activity') }}</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" class="mt-4 space-y-4">
                <!-- Project settings card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('app.project.settings_card_title') }}</CardTitle>
                        <CardDescription>{{ t('app.project.settings_card_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl class="flex flex-wrap gap-x-10 gap-y-5 text-sm">
                            <div v-if="project.daily_rate_formatted">
                                <dt class="text-xs text-muted-foreground">{{ t('app.project.daily_rate') }}</dt>
                                <dd class="mt-1 font-medium">{{ project.daily_rate_formatted }}{{ t('app.common.per_day') }}</dd>
                            </div>
                            <div v-if="project.hourly_rate_formatted">
                                <dt class="text-xs text-muted-foreground">{{ t('app.project.hourly_rate') }}</dt>
                                <dd class="mt-1 font-medium">{{ project.hourly_rate_formatted }}{{ t('app.common.per_hr') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">{{ t('app.project.reference_day') }}</dt>
                                <dd class="mt-1 font-medium">{{ project.daily_reference_hours }}h</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">{{ t('app.stats.rounding') }}</dt>
                                <dd class="mt-1 font-medium">{{ project.rounding.label }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <!-- Tracked folders card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>{{ t('app.project.repositories') }}</CardTitle>
                                <CardDescription class="mt-1">{{ t('app.project.repositories_description') }}</CardDescription>
                            </div>
                            <RepositorySheet :project="project">
                                <Button size="sm" variant="outline">{{ t('app.project.add_repository') }}</Button>
                            </RepositorySheet>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <ItemGroup v-if="project.repositories?.length" class="gap-1.5">
                            <Item v-for="repo in project.repositories" :key="repo.id" variant="muted" size="sm">
                                <ItemContent>
                                    <ItemTitle>{{ repo.name }}</ItemTitle>
                                    <ItemDescription class="font-mono text-xs">{{ repo.local_path }}</ItemDescription>
                                </ItemContent>
                                <ItemActions>
                                    <Button variant="ghost" size="sm" class="text-destructive" @click="repoToDelete = repo">
                                        {{ t('app.common.remove') }}
                                    </Button>
                                </ItemActions>
                            </Item>
                        </ItemGroup>
                        <Empty v-else>
                            <EmptyTitle>{{ t('app.project.no_repos') }}</EmptyTitle>
                            <EmptyDescription>{{ t('app.project.no_repos_description') }}</EmptyDescription>
                            <RepositorySheet :project="project">
                                <Button size="sm" variant="outline">{{ t('app.project.add_repository') }}</Button>
                            </RepositorySheet>
                        </Empty>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="activity" class="mt-4">
                <div class="h-125">
                    <ActivityLog />
                </div>
            </TabsContent>
        </Tabs>

        <ConfirmDialog
            :open="repoToDelete !== null"
            :title="t('app.project.remove_repository')"
            :message="repoToDelete ? t('app.project.remove_repository_confirm', { name: repoToDelete.name }) : ''"
            :confirm-label="t('app.common.remove')"
            @confirm="removeRepo"
            @cancel="repoToDelete = null"
        />
    </AppLayout>
</template>
