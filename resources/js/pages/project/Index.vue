<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import * as projectRoutes from '@/actions/App/Http/Controllers/ProjectController';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Project, Paginator } from '@/types';

defineProps<{
    projects: Paginator<Project & { repositories_count: number }>;
}>();
</script>

<template>
    <AppLayout title="Projects">
        <template #header>
            <PageHeader title="Projects" />
        </template>

        <div v-if="projects.data.length === 0" class="mt-6 text-center">
            <p class="text-sm text-muted-foreground">No projects yet.</p>
        </div>

        <div v-else class="flex flex-col gap-1.5">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="projectRoutes.show(project.id)"
                class="flex items-center justify-between rounded-lg border bg-card px-5 py-4 text-card-foreground transition-colors hover:bg-accent"
            >
                <div class="flex items-center gap-3">
                    <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                    <div>
                        <p class="text-sm font-medium">{{ project.name }}</p>
                        <p v-if="project.client" class="mt-0.5 text-xs text-muted-foreground">
                            {{ project.client.name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Badge v-if="!project.is_active" variant="outline">Inactive</Badge>
                    <Badge variant="secondary">
                        {{ project.repositories_count }}
                        {{ project.repositories_count === 1 ? 'repository' : 'repositories' }}
                    </Badge>
                </div>
            </Link>

            <div v-if="projects.last_page > 1" class="mt-4 flex items-center justify-between">
                <Button v-if="projects.prev_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="projects.prev_page_url">← Previous</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">← Previous</span>

                <span class="text-xs text-muted-foreground">Page {{ projects.current_page }} of {{ projects.last_page }}</span>

                <Button v-if="projects.next_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="projects.next_page_url">Next →</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
