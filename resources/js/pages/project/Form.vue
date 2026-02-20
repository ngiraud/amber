<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    isEditing.value ? projectRoutes.update({ client: props.client, project: props.project! }) : projectRoutes.store(props.client),
);

const color = ref(props.project?.color ?? '#6366f1');
const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="isEditing ? `Edit — ${project!.name}` : 'New project'">
        <div class="max-w-lg">
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

                    <template v-if="isEditing">
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink as-child>
                                <Link :href="projectRoutes.show({ client, project: project! })">{{ project!.name }}</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                    </template>

                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>{{ isEditing ? 'Edit' : 'New project' }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <h1 class="mt-4 text-xl font-semibold">
                {{ isEditing ? 'Edit project' : 'New project' }}
            </h1>

            <Form
                class="mt-6 flex flex-col gap-5"
                :action="action"
                :transform="(data) => ({ ...data, is_active: data.is_active === '1' })"
                #default="{ errors, processing }"
            >
                <InputField label="Name" :error="errors.name" required>
                    <Input name="name" type="text" :default-value="project?.name" :placeholder="isEditing ? undefined : 'My project'" autofocus />
                </InputField>

                <InputField label="Color" :error="errors.color">
                    <ColorPicker v-model="color" name="color" />
                </InputField>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Hourly rate (€)" :error="errors.hourly_rate">
                        <Input
                            name="hourly_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :default-value="project?.hourly_rate ?? undefined"
                            placeholder="0.00"
                        />
                    </InputField>

                    <InputField label="Daily rate (€)" :error="errors.daily_rate">
                        <Input
                            name="daily_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :default-value="project?.daily_rate ?? undefined"
                            placeholder="0.00"
                        />
                    </InputField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Daily reference hours" :error="errors.daily_reference_hours">
                        <Input name="daily_reference_hours" type="number" min="1" max="24" :default-value="project?.daily_reference_hours ?? 7" />
                    </InputField>

                    <InputField label="Rounding" :error="errors.rounding">
                        <select
                            name="rounding"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                        class="h-4 w-4 rounded border-input accent-foreground"
                    />
                    <span class="text-sm">Active project</span>
                </label>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create project' }}
                        </Button>

                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="isEditing ? projectRoutes.show({ client, project: project! }) : clientRoutes.show(client)"> Cancel </Link>
                        </Button>
                    </div>

                    <Button
                        v-if="isEditing"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        type="button"
                        @click="confirmDelete = true"
                    >
                        Delete project
                    </Button>
                </div>
            </Form>
        </div>

        <Form v-if="isEditing" :action="projectRoutes.destroy({ client, project: project! })" #default="{ submit }">
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
