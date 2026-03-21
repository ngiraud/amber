<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProjectSheet from '@/components/ProjectSheet.vue';
import RepositorySheet from '@/components/RepositorySheet.vue';
import ToggleProjectStatusDialog from '@/components/ToggleProjectStatusDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import repositories from '@/routes/projects/repositories';
import type { Client, Project, ProjectRepository } from '@/types';

const props = defineProps<{
    client: Client;
    project: Project;
    clients: Client[];
}>();

const repoToDelete = ref<ProjectRepository | null>(null);
const confirmDelete = ref(false);

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

        <div class="mt-4 flex shrink-0 flex-wrap gap-6 text-sm text-muted-foreground">
            <span v-if="project.daily_rate_formatted">
                <span class="font-medium text-foreground">{{ project.daily_rate_formatted }}</span
                >{{ t('app.common.per_day') }}
            </span>
            <span v-if="project.hourly_rate_formatted">
                <span class="font-medium text-foreground">{{ project.hourly_rate_formatted }}</span
                >{{ t('app.common.per_hr') }}
            </span>
            <span>
                <span class="font-medium text-foreground">{{ project.daily_reference_hours }}h</span> {{ t('app.project.reference_day') }}
            </span>
            <span>
                {{ t('app.project.rounding_label') }} <span class="font-medium text-foreground">{{ project.rounding.label }}</span>
            </span>
        </div>

        <div class="mt-8 shrink-0">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">{{ t('app.project.repositories') }}</h2>

                <RepositorySheet :project="project">
                    <Button size="sm" variant="outline">{{ t('app.project.add_repository') }}</Button>
                </RepositorySheet>
            </div>

            <div v-if="project.repositories?.length" class="mt-3 flex flex-col gap-1.5">
                <div
                    v-for="repo in project.repositories"
                    :key="repo.id"
                    class="flex items-center justify-between rounded-lg border bg-card px-4 py-3"
                >
                    <div>
                        <p class="text-sm font-medium">{{ repo.name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-muted-foreground">{{ repo.local_path }}</p>
                    </div>

                    <Button variant="ghost" size="sm" class="text-destructive" @click="repoToDelete = repo">
                        {{ t('app.common.remove') }}
                    </Button>
                </div>
            </div>

            <Empty v-else class="mt-3">
                <EmptyTitle>{{ t('app.project.no_repos') }}</EmptyTitle>
                <EmptyDescription>{{ t('app.project.no_repos_description') }}</EmptyDescription>
                <RepositorySheet :project="project">
                    <Button size="sm" variant="outline">{{ t('app.project.add_repository') }}</Button>
                </RepositorySheet>
            </Empty>
        </div>

        <ConfirmDialog
            :open="repoToDelete !== null"
            :title="t('app.project.remove_repository')"
            :message="repoToDelete ? t('app.project.remove_repository_confirm', { name: repoToDelete.name }) : ''"
            :confirm-label="t('app.common.remove')"
            @confirm="removeRepo"
            @cancel="repoToDelete = null"
        />

        <div class="mt-8 flex min-h-0 flex-1 flex-col">
            <h2 class="shrink-0 text-base font-semibold">{{ t('app.common.recent_activity') }}</h2>

            <div class="mt-3 min-h-0 flex-1">
                <ActivityLog />
            </div>
        </div>
    </AppLayout>
</template>
