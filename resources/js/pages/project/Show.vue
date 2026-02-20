<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import repositories from '@/routes/projects/repositories';
import type { Client, Project, ProjectRepository } from '@/types';

defineProps<{
    client: Client;
    project: Project;
}>();

const repoToDelete = ref<ProjectRepository | null>(null);

const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="project.name">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="clientRoutes.index()" class="text-sm text-gray-500 hover:text-gray-700"> Clients </Link>
                <span class="text-gray-300">/</span>
                <Link :href="clientRoutes.show(client)" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ client.name }}
                </Link>
                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-900">{{ project.name }}</span>
            </div>

            <div class="flex gap-2">
                <Link
                    :href="projectRoutes.edit({ client, project })"
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

        <!-- Project details -->
        <div class="mt-6 flex items-center gap-3">
            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: project.color }" />
            <h1 class="text-xl font-semibold text-gray-900">{{ project.name }}</h1>
            <span v-if="!project.is_active" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500"> Inactive </span>
        </div>

        <div class="mt-4 flex gap-6 text-sm text-gray-500">
            <span v-if="project.daily_rate_formatted">
                <span class="font-medium text-gray-900">{{ project.daily_rate_formatted }}</span>
                /day
            </span>
            <span v-if="project.hourly_rate_formatted">
                <span class="font-medium text-gray-900">{{ project.hourly_rate_formatted }}</span>
                /hr
            </span>
            <span>
                <span class="font-medium text-gray-900">{{ project.daily_reference_hours }}h</span>
                reference day
            </span>
            <span>
                Rounding: <span class="font-medium text-gray-900">{{ project.rounding.label }}</span>
            </span>
        </div>

        <!-- Repositories -->
        <div class="mt-8">
            <h2 class="text-base font-semibold text-gray-900">Repositories</h2>

            <div v-if="project.repositories?.length" class="mt-3 flex flex-col gap-2">
                <div
                    v-for="repo in project.repositories"
                    :key="repo.id"
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ repo.name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-gray-400">{{ repo.local_path }}</p>
                    </div>

                    <button type="button" class="text-sm text-red-500 hover:text-red-700" @click="repoToDelete = repo">Remove</button>
                </div>
            </div>

            <div v-else class="mt-3 text-sm text-gray-500">No repositories linked yet.</div>

            <!-- Add repository form -->
            <Form class="mt-4 flex flex-col gap-3" :action="repositories.store(project)" #default="{ errors, processing, reset }">
                <div class="grid grid-cols-2 gap-3">
                    <InputField label="Repository name" :error="errors.name">
                        <input
                            name="name"
                            type="text"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            placeholder="my-repo"
                        />
                    </InputField>

                    <InputField label="Local path" :error="errors.local_path">
                        <input
                            name="local_path"
                            type="text"
                            class="rounded-md border border-gray-300 px-3 py-2 font-mono text-sm focus:border-gray-400 focus:outline-none"
                            placeholder="/Users/me/code/my-repo"
                        />
                    </InputField>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        {{ processing ? 'Adding…' : 'Add repository' }}
                    </button>
                </div>
            </Form>
        </div>

        <Form v-if="repoToDelete" :action="repositories.destroy(project, repoToDelete)" #default="{ submit }">
            <ConfirmDialog
                :open="true"
                title="Remove repository"
                :message="`Remove ${repoToDelete.name} from this project?`"
                confirm-label="Remove"
                @confirm="submit"
                @cancel="repoToDelete = null"
            />
        </Form>
    </AppLayout>
</template>
