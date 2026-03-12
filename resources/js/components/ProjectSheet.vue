<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { ChevronDownIcon, PlusIcon, TrashIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import * as projectRoutes from '@/routes/projects';
import type { Client, Project } from '@/types';

const props = defineProps<{
    client?: Client;
    project?: Project;
    clients: Client[];
}>();

const page = usePage();
const defaults = computed(() => page.props.generalSettings);
type Repo = { name: string; local_path: string };

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
const showAdvanced = ref(false);
const showRepos = ref(true);
const repos = ref<Repo[]>([]);

watch(open, (isOpen) => {
    if (isOpen) {
        color.value = props.project?.color ?? '#6366f1';
        showAdvanced.value = false;
        showRepos.value = true;
        repos.value = [];
    }
});

function addRepo(): void {
    repos.value.push({ name: '', local_path: '' });
}

function removeRepo(index: number): void {
    repos.value.splice(index, 1);
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit project' : 'New project' }}</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 overflow-y-auto px-4 py-2"
                :action="action"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <InputField label="Client" :error="errors.client_id" required>
                    <NativeSelect name="client_id" :model-value="selectedClientId">
                        <NativeSelectOption value="" disabled>Select a client…</NativeSelectOption>
                        <NativeSelectOption v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </NativeSelectOption>
                    </NativeSelect>
                </InputField>

                <InputField label="Name" :error="errors.name" required>
                    <Input name="name" type="text" :default-value="project?.name" :placeholder="isEditing ? undefined : 'My project'" autofocus />
                </InputField>

                <InputField label="Color" :error="errors.color">
                    <ColorPicker v-model="color" name="color" />
                </InputField>

                <!-- Repositories section (create mode only) -->
                <Collapsible v-if="!isEditing" v-model:open="showRepos">
                    <CollapsibleTrigger class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                        <ChevronDownIcon class="h-3.5 w-3.5 transition-transform duration-200" :class="{ '-rotate-90': !showRepos }" />
                        Repositories
                        <span v-if="repos.length" class="ml-1 text-xs text-foreground">({{ repos.length }})</span>
                    </CollapsibleTrigger>

                    <CollapsibleContent class="flex flex-col gap-3 pt-3">
                        <div v-for="(repo, i) in repos" :key="i" class="flex flex-col gap-2 rounded-md border p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-muted-foreground">Repository {{ i + 1 }}</span>
                                <button type="button" class="text-muted-foreground transition-colors hover:text-destructive" @click="removeRepo(i)">
                                    <TrashIcon class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <InputField label="Name" :error="(errors as Record<string, string>)[`repositories.${i}.name`]">
                                <Input :name="`repositories[${i}][name]`" type="text" placeholder="my-repo" v-model="repo.name" />
                            </InputField>
                            <InputField label="Local path" :error="(errors as Record<string, string>)[`repositories.${i}.local_path`]">
                                <Input
                                    :name="`repositories[${i}][local_path]`"
                                    type="text"
                                    placeholder="/Users/me/code/my-repo"
                                    class="font-mono"
                                    v-model="repo.local_path"
                                />
                            </InputField>
                        </div>

                        <Button type="button" variant="outline" size="sm" class="w-full" @click="addRepo">
                            <PlusIcon class="mr-1.5 h-3.5 w-3.5" />
                            Add a repository
                        </Button>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Advanced section -->
                <Collapsible v-model:open="showAdvanced">
                    <CollapsibleTrigger class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                        <ChevronDownIcon class="h-3.5 w-3.5 transition-transform duration-200" :class="{ '-rotate-90': !showAdvanced }" />
                        Advanced settings
                    </CollapsibleTrigger>

                    <CollapsibleContent class="flex flex-col gap-4 pt-3">
                        <div class="grid grid-cols-2 gap-4">
                            <InputField label="Hourly rate (€)" :error="errors.hourly_rate">
                                <Input
                                    name="hourly_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :default-value="project?.hourly_rate ?? defaults.default_hourly_rate ?? undefined"
                                    placeholder="0.00"
                                />
                            </InputField>

                            <InputField label="Daily rate (€)" :error="errors.daily_rate">
                                <Input
                                    name="daily_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :default-value="project?.daily_rate ?? defaults.default_daily_rate ?? undefined"
                                    placeholder="0.00"
                                />
                            </InputField>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <InputField label="Daily reference hours" :error="errors.daily_reference_hours">
                                <Input
                                    name="daily_reference_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    :default-value="project?.daily_reference_hours ?? defaults.default_daily_reference_hours ?? 8"
                                />
                            </InputField>

                            <InputField label="Rounding" :error="errors.rounding">
                                <NativeSelect name="rounding" :model-value="project?.rounding.value ?? defaults.default_rounding_strategy ?? 15">
                                    <NativeSelectOption v-for="option in ROUNDING_OPTIONS" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </NativeSelectOption>
                                </NativeSelect>
                            </InputField>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <SheetFooter class="px-0">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create project' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
