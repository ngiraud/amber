<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import repositories from '@/routes/projects/repositories';
import type { Client, Project, ProjectRepository } from '@/types';

const props = defineProps<{
    client: Client;
    project: Project;
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
        <div class="flex items-center justify-between">
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

            <div class="flex gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="projectRoutes.edit({ client, project })">Edit</Link>
                </Button>

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
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: project.color }" />
            <h1 class="text-xl font-semibold">{{ project.name }}</h1>
            <Badge v-if="!project.is_active" variant="secondary">Inactive</Badge>
        </div>

        <div class="mt-4 flex flex-wrap gap-6 text-sm text-muted-foreground">
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

        <div class="mt-8">
            <h2 class="text-base font-semibold">Repositories</h2>

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

            <Form class="mt-4 flex flex-col gap-3" :action="repositories.store(project)" #default="{ errors, processing }">
                <div class="grid grid-cols-2 gap-3">
                    <InputField label="Repository name" :error="errors.name">
                        <Input name="name" type="text" placeholder="my-repo" />
                    </InputField>

                    <InputField label="Local path" :error="errors.local_path">
                        <Input name="local_path" type="text" placeholder="/Users/me/code/my-repo" class="font-mono" />
                    </InputField>
                </div>

                <div>
                    <Button type="submit" variant="outline" size="sm" :disabled="processing">
                        {{ processing ? 'Adding…' : 'Add repository' }}
                    </Button>
                </div>
            </Form>
        </div>

        <ConfirmDialog
            :open="repoToDelete !== null"
            title="Remove repository"
            :message="repoToDelete ? `Remove ${repoToDelete.name} from this project?` : ''"
            confirm-label="Remove"
            @confirm="removeRepo"
            @cancel="repoToDelete = null"
        />
    </AppLayout>
</template>
