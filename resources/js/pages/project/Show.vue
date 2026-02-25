<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProjectSheet from '@/components/ProjectSheet.vue';
import RepositorySheet from '@/components/RepositorySheet.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import repositories from '@/routes/projects/repositories';
import type { ActivityEvent, Client, Paginator, Project, ProjectRepository } from '@/types';

const props = defineProps<{
    client: Client;
    project: Project;
    events: Paginator<ActivityEvent>;
    hasNewEvents: boolean;
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
    <AppLayout :title="project.name">
        <template #header>
            <PageHeader>
                <template #breadcrumb>
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="clientRoutes.index()">Clients</Link>
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
                        <Badge v-if="!project.is_active" variant="secondary">Inactive</Badge>
                    </div>
                </template>

                <template #actions>
                    <ProjectSheet :client="client" :project="project">
                        <Button variant="outline" size="sm">Edit</Button>
                    </ProjectSheet>

                    <Button variant="destructive" size="sm" @click="confirmDelete = true">Delete</Button>

                    <Form :action="projectRoutes.destroy({ client, project: project! })" #default="{ submit }">
                        <ConfirmDialog
                            :open="confirmDelete"
                            title="Delete project"
                            :message="`Are you sure you want to delete ${project!.name}?`"
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
                >/day
            </span>
            <span v-if="project.hourly_rate_formatted">
                <span class="font-medium text-foreground">{{ project.hourly_rate_formatted }}</span
                >/hr
            </span>
            <span>
                <span class="font-medium text-foreground">{{ project.daily_reference_hours }}h</span> reference day
            </span>
            <span>
                Rounding: <span class="font-medium text-foreground">{{ project.rounding.label }}</span>
            </span>
        </div>

        <div class="mt-8 shrink-0">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Repositories</h2>

                <RepositorySheet :project="project">
                    <Button size="sm" variant="outline">Add repository</Button>
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

                    <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="repoToDelete = repo"> Remove </Button>
                </div>
            </div>

            <p v-else class="mt-3 text-sm text-muted-foreground">No repositories linked yet.</p>
        </div>

        <ConfirmDialog
            :open="repoToDelete !== null"
            title="Remove repository"
            :message="repoToDelete ? `Remove ${repoToDelete.name} from this project?` : ''"
            confirm-label="Remove"
            @confirm="removeRepo"
            @cancel="repoToDelete = null"
        />

        <div class="mt-8 flex min-h-0 flex-1 flex-col">
            <h2 class="shrink-0 text-base font-semibold">Recent Activity</h2>

            <div class="mt-3 min-h-0 flex-1">
                <ActivityLog :events="events" :has-new-events="hasNewEvents" scroll-class="h-full overflow-y-auto" />
            </div>
        </div>
    </AppLayout>
</template>
