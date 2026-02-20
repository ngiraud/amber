<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import type { Client, Project } from '@/types';

const props = defineProps<{
    client: Client;
    project?: Project;
}>();

const ROUNDING_OPTIONS = [
    { value: 15, label: '15 min' },
    { value: 30, label: '30 min' },
    { value: 60, label: '1 hour' },
];

const isEditing = computed(() => !!props.project);
const action = computed(() =>
    isEditing.value
        ? projectRoutes.update({ client: props.client, project: props.project! })
        : projectRoutes.store(props.client),
);

const color = ref(props.project?.color ?? '#6366f1');
const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="isEditing ? `Edit — ${project!.name}` : 'New project'">
        <div class="max-w-lg">
            <div class="flex items-center gap-3">
                <Link :href="clientRoutes.index()" class="text-sm text-gray-500 hover:text-gray-700"> Clients </Link>
                <span class="text-gray-300">/</span>
                <Link :href="clientRoutes.show(client)" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ client.name }}
                </Link>

                <template v-if="isEditing">
                    <span class="text-gray-300">/</span>
                    <Link :href="projectRoutes.show({ client, project: project! })" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ project!.name }}
                    </Link>
                </template>

                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-900">{{ isEditing ? 'Edit' : 'New project' }}</span>
            </div>

            <h1 class="mt-4 text-xl font-semibold text-gray-900">
                {{ isEditing ? 'Edit project' : 'New project' }}
            </h1>

            <Form
                class="mt-6 flex flex-col gap-5"
                :action="action"
                :transform="(data) => ({ ...data, is_active: data.is_active === '1' })"
                #default="{ errors, processing }"
            >
                <InputField label="Name" :error="errors.name" required>
                    <input
                        name="name"
                        type="text"
                        :defaultValue="project?.name"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        :placeholder="isEditing ? undefined : 'My project'"
                        autofocus
                    />
                </InputField>

                <InputField label="Color" :error="errors.color">
                    <ColorPicker v-model="color" name="color" />
                </InputField>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Hourly rate (€)" :error="errors.hourly_rate">
                        <input
                            name="hourly_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :defaultValue="project?.hourly_rate ?? undefined"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            placeholder="0.00"
                        />
                    </InputField>

                    <InputField label="Daily rate (€)" :error="errors.daily_rate">
                        <input
                            name="daily_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :defaultValue="project?.daily_rate ?? undefined"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                            placeholder="0.00"
                        />
                    </InputField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Daily reference hours" :error="errors.daily_reference_hours">
                        <input
                            name="daily_reference_hours"
                            type="number"
                            min="1"
                            max="24"
                            :defaultValue="project?.daily_reference_hours ?? 7"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        />
                    </InputField>

                    <InputField label="Rounding" :error="errors.rounding">
                        <select
                            name="rounding"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        >
                            <option
                                v-for="option in ROUNDING_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                                :selected="option.value === (project?.rounding.value ?? 15)"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </InputField>
                </div>

                <label class="flex items-center gap-2.5">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        :checked="project?.is_active ?? true"
                        class="rounded border-gray-300"
                    />
                    <span class="text-sm text-gray-700">Active project</span>
                </label>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : (isEditing ? 'Save changes' : 'Create project') }}
                        </button>

                        <Link
                            :href="isEditing ? projectRoutes.show({ client, project: project! }) : clientRoutes.show(client)"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Cancel
                        </Link>
                    </div>

                    <button
                        v-if="isEditing"
                        type="button"
                        class="text-sm text-red-600 hover:text-red-700"
                        @click="confirmDelete = true"
                    >
                        Delete project
                    </button>
                </div>
            </Form>
        </div>

        <Form
            v-if="isEditing"
            :action="projectRoutes.destroy({ client, project: project! })"
            #default="{ submit }"
        >
            <ConfirmDialog
                :open="confirmDelete"
                title="Delete project"
                :message="`Are you sure you want to delete ${project!.name}?`"
                @confirm="submit"
                @cancel="confirmDelete = false"
            />
        </Form>
    </AppLayout>
</template>
