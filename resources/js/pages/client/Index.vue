<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import type { Client, Paginator } from '@/types';

defineProps<{
    clients: Paginator<Client>;
}>();
</script>

<template>
    <AppLayout title="Clients">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Clients</h1>

            <Link :href="clientRoutes.create()" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                New client
            </Link>
        </div>

        <div v-if="clients.data.length === 0" class="mt-12 text-center">
            <p class="text-sm text-gray-500">No clients yet.</p>
            <Link :href="clientRoutes.create()" class="mt-3 inline-block text-sm font-medium text-gray-900 underline"> Add your first client </Link>
        </div>

        <div v-else class="mt-6 flex flex-col gap-2">
            <Link
                v-for="client in clients.data"
                :key="client.id"
                :href="clientRoutes.show(client)"
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-5 py-4 transition-colors hover:border-gray-300 hover:bg-gray-50"
            >
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ client.name }}</p>
                    <p v-if="client.notes" class="mt-0.5 line-clamp-1 text-xs text-gray-400">
                        {{ client.notes }}
                    </p>
                </div>

                <span class="text-xs text-gray-400">
                    {{ client.projects_count ?? 0 }}
                    {{ (client.projects_count ?? 0) === 1 ? 'project' : 'projects' }}
                </span>
            </Link>

            <div v-if="clients.last_page > 1" class="mt-4 flex items-center justify-between">
                <Link v-if="clients.prev_page_url" :href="clients.prev_page_url" class="text-sm text-gray-500 hover:text-gray-700"> ← Previous </Link>
                <span v-else class="text-sm text-gray-300">← Previous</span>

                <span class="text-xs text-gray-400"> Page {{ clients.current_page }} of {{ clients.last_page }} </span>

                <Link v-if="clients.next_page_url" :href="clients.next_page_url" class="text-sm text-gray-500 hover:text-gray-700"> Next → </Link>
                <span v-else class="text-sm text-gray-300">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
