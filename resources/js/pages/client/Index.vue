<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ClientSheet from '@/components/ClientSheet.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import type { Client, Paginator } from '@/types';

defineProps<{
    clients: Paginator<Client>;
}>();
</script>

<template>
    <AppLayout title="Clients">
        <template #header>
            <PageHeader title="Clients">
                <template #actions>
                    <ClientSheet>
                        <Button size="sm">New client</Button>
                    </ClientSheet>
                </template>
            </PageHeader>
        </template>

        <div v-if="clients.data.length === 0" class="mt-6 text-center">
            <p class="text-sm text-muted-foreground">No clients yet.</p>
            <ClientSheet>
                <button class="mt-3 text-sm font-medium underline underline-offset-4">Add your first client</button>
            </ClientSheet>
        </div>

        <div v-else class="flex flex-col gap-1.5">
            <Link
                v-for="client in clients.data"
                :key="client.id"
                :href="clientRoutes.show(client)"
                class="flex items-center justify-between rounded-lg border bg-card px-5 py-4 text-card-foreground transition-colors hover:bg-accent"
            >
                <div>
                    <p class="text-sm font-medium">{{ client.name }}</p>
                    <p v-if="client.notes" class="mt-0.5 line-clamp-1 text-xs text-muted-foreground">
                        {{ client.notes }}
                    </p>
                </div>

                <Badge variant="secondary">
                    {{ client.projects_count ?? 0 }}
                    {{ (client.projects_count ?? 0) === 1 ? 'project' : 'projects' }}
                </Badge>
            </Link>

            <div v-if="clients.last_page > 1" class="mt-4 flex items-center justify-between">
                <Button v-if="clients.prev_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="clients.prev_page_url">← Previous</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">← Previous</span>

                <span class="text-xs text-muted-foreground">Page {{ clients.current_page }} of {{ clients.last_page }}</span>

                <Button v-if="clients.next_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="clients.next_page_url">Next →</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
