<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import type { ActivityEvent, Client, Paginator } from '@/types';

defineProps<{
    client: Client;
    events: Paginator<ActivityEvent>;
    hasNewEvents: boolean;
}>();

const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="client.name">
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
                        <BreadcrumbPage>{{ client.name }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <div class="flex gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="clientRoutes.edit(client)">Edit</Link>
                </Button>

                <Button variant="destructive" size="sm" @click="confirmDelete = true">Delete</Button>

                <Form :action="clientRoutes.destroy(client!)" #default="{ submit }">
                    <ConfirmDialog
                        :open="confirmDelete"
                        title="Delete client"
                        :message="`Are you sure you want to delete ${client!.name}? All associated projects will be deleted.`"
                        @confirm="submit"
                        @cancel="confirmDelete = false"
                    />
                </Form>
            </div>
        </div>

        <div class="mt-6">
            <p v-if="client.notes" class="mb-6 text-sm text-muted-foreground">{{ client.notes }}</p>

            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Projects</h2>

                <Button size="sm" as-child>
                    <Link :href="projectRoutes.create(client)">Add project</Link>
                </Button>
            </div>

            <div v-if="!client.projects?.length" class="mt-6 text-center">
                <p class="text-sm text-muted-foreground">No projects yet.</p>
            </div>

            <div v-else class="mt-4 grid grid-cols-2 gap-3">
                <Link
                    v-for="project in client.projects"
                    :key="project.id"
                    :href="projectRoutes.show({ client: client, project: project })"
                    class="flex flex-col gap-2 rounded-lg border bg-card p-4 text-card-foreground transition-colors hover:bg-accent"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                        <span class="text-sm font-medium">{{ project.name }}</span>
                        <Badge v-if="!project.is_active" variant="secondary" class="ml-auto text-xs">Inactive</Badge>
                    </div>

                    <div class="flex gap-4 text-xs text-muted-foreground">
                        <span v-if="project.daily_rate_formatted">
                            <span class="font-medium text-foreground">{{ project.daily_rate_formatted }}</span
                            >/day
                        </span>
                        <span v-if="project.hourly_rate_formatted">
                            <span class="font-medium text-foreground">{{ project.hourly_rate_formatted }}</span
                            >/hr
                        </span>
                    </div>
                </Link>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-base font-semibold">Recent Activity</h2>

            <div class="mt-3">
                <ActivityLog :events="events" :has-new-events="hasNewEvents" scroll-class="max-h-80 overflow-y-auto" />
            </div>
        </div>
    </AppLayout>
</template>
