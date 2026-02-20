<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import type { Client } from '@/types';

defineProps<{
    client: Client;
}>();

const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="client.name">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="clientRoutes.index()" class="text-sm text-gray-500 hover:text-gray-700"> Clients </Link>
                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-900">{{ client.name }}</span>
            </div>

            <div class="flex gap-2">
                <Link
                    :href="clientRoutes.edit(client)"
                    class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Edit
                </Link>

                <button
                    type="button"
                    class="cursor-pointer rounded-md border border-red-600 bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-red-700"
                    @click="confirmDelete = true"
                >
                    Delete
                </button>

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
            <div v-if="client.notes" class="mb-6 text-sm text-gray-600">{{ client.notes }}</div>

            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Projects</h2>

                <Link
                    :href="projectRoutes.create(client)"
                    class="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700"
                >
                    Add project
                </Link>
            </div>

            <div v-if="!client.projects?.length" class="mt-6 text-center">
                <p class="text-sm text-gray-500">No projects yet.</p>
            </div>

            <div v-else class="mt-4 grid grid-cols-2 gap-4">
                <Link
                    v-for="project in client.projects"
                    :key="project.id"
                    :href="projectRoutes.show({ client: client, project: project })"
                    class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 transition-colors hover:border-gray-300 hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                        <span class="text-sm font-medium text-gray-900">{{ project.name }}</span>
                        <span v-if="!project.is_active" class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500"> Inactive </span>
                    </div>

                    <div class="flex gap-4 text-xs text-gray-400">
                        <span v-if="project.daily_rate_formatted"> {{ project.daily_rate_formatted }}/day </span>
                        <span v-if="project.hourly_rate_formatted"> {{ project.hourly_rate_formatted }}/hr </span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
