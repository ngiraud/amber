<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import * as projectRoutes from '@/routes/projects';
import type { Client, Project } from '@/types';

const props = defineProps<{
    client?: Client;
    project?: Project;
    clients: Client[];
}>();

const ROUNDING_OPTIONS = [
    { value: 15, label: '15 min' },
    { value: 30, label: '30 min' },
    { value: 60, label: '1 hour' },
];

const open = ref(false);
const isEditing = computed(() => !!props.project);
const action = computed(() => (isEditing.value ? projectRoutes.update(props.project!) : projectRoutes.store()));
const selectedClientId = computed(() => props.project?.client_id ?? props.client?.id);

const color = ref(props.project?.color ?? '#6366f1');

watch(open, (isOpen) => {
    if (isOpen) {
        color.value = props.project?.color ?? '#6366f1';
    }
});
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right" class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit project' : 'New project' }}</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 overflow-y-auto px-4 py-2"
                :action="action"
                :transform="(data) => ({ ...data, is_active: data.is_active === '1' })"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <InputField label="Client" :error="errors.client_id" required>
                    <select
                        name="client_id"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled :selected="!selectedClientId">Select a client…</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id" :selected="c.id === selectedClientId">
                            {{ c.name }}
                        </option>
                    </select>
                </InputField>

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

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create project' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
