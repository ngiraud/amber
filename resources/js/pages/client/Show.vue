<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ClientSheet from '@/components/ClientSheet.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProjectSheet from '@/components/ProjectSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import type { Client } from '@/types';

defineProps<{
    client: Client;
    clients: Client[];
}>();

const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="client.name" :breadcrumb="['Clients', client.name]">
        <template #header>
            <PageHeader :title="client.name">
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
                                <BreadcrumbPage>{{ client.name }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </template>

                <template #actions>
                    <ClientSheet :client="client">
                        <Button variant="outline" size="sm">Edit</Button>
                    </ClientSheet>

                    <Button variant="destructive" size="sm" @click="confirmDelete = true">Delete</Button>

                    <Form :action="clientRoutes.destroy(client)" #default="{ submit }">
                        <ConfirmDialog
                            :open="confirmDelete"
                            title="Delete client"
                            :message="`Are you sure you want to delete ${client.name}? All associated projects, repositories, sessions, and activity events will be permanently deleted.`"
                            @confirm="submit"
                            @cancel="confirmDelete = false"
                        />
                    </Form>
                </template>
            </PageHeader>
        </template>

        <div class="shrink-0">
            <p v-if="client.notes" class="mb-6 text-sm text-muted-foreground">{{ client.notes }}</p>

            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Projects</h2>

                <ProjectSheet :client="client" :clients="clients">
                    <Button size="sm">Add project</Button>
                </ProjectSheet>
            </div>

            <Empty v-if="!client.projects?.length" class="mt-6">
                <EmptyTitle>No projects yet</EmptyTitle>
                <EmptyDescription>Add a project to start tracking time for this client.</EmptyDescription>
                <ProjectSheet :client="client" :clients="clients">
                    <Button size="sm">Add project</Button>
                </ProjectSheet>
            </Empty>

            <div v-else class="mt-4 grid grid-cols-2 gap-3">
                <Link
                    v-for="project in client.projects"
                    :key="project.id"
                    :href="projectRoutes.show(project)"
                    class="flex flex-col gap-2 rounded-lg border bg-card p-4 text-card-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                        <span class="text-sm font-medium">{{ project.name }}</span>
                        <Badge v-if="!project.is_active" variant="secondary" class="ml-auto text-xs">Inactive</Badge>
                    </div>

                    <div class="flex gap-4 text-xs text-muted-foreground">
                        <span v-if="project.daily_rate_formatted">
                            <span class="font-medium">{{ project.daily_rate_formatted }}</span
                            >/day
                        </span>
                        <span v-if="project.hourly_rate_formatted">
                            <span class="font-medium">{{ project.hourly_rate_formatted }}</span
                            >/hr
                        </span>
                    </div>
                </Link>
            </div>
        </div>

        <div class="mt-8 flex min-h-0 flex-1 flex-col">
            <h2 class="shrink-0 text-base font-semibold">Recent Activity</h2>

            <div class="mt-3 min-h-0 flex-1">
                <ActivityLog />
            </div>
        </div>
    </AppLayout>
</template>
