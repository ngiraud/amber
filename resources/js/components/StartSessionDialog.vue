<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import * as sessionRoutes from '@/routes/sessions';
import type { Project } from '@/types';

defineProps<{
    projects: Project[];
}>();

const open = ref(false);
const mode = ref<'timer' | 'past'>('timer');

const timerForm = useForm({
    project_id: '',
    notes: '',
});

const pastForm = useForm({
    project_id: '',
    started_at: '',
    ended_at: '',
    description: '',
    notes: '',
});

function closeAndReset(): void {
    open.value = false;
    timerForm.reset();
    pastForm.reset();
    mode.value = 'timer';
}

function submitTimer(): void {
    timerForm.submit(sessionRoutes.start(), {
        onSuccess: () => closeAndReset(),
    });
}

function submitPast(): void {
    pastForm.submit(sessionRoutes.store(), {
        onSuccess: () => closeAndReset(),
    });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right" class="w-96">
            <SheetHeader>
                <SheetTitle>Add Session</SheetTitle>
            </SheetHeader>

            <Tabs v-model="mode" class="mt-6 px-4">
                <TabsList class="w-full">
                    <TabsTrigger value="timer" class="flex-1">Start now</TabsTrigger>
                    <TabsTrigger value="past" class="flex-1">Past session</TabsTrigger>
                </TabsList>

                <!-- Timer mode -->
                <TabsContent value="timer" class="mt-5 flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label for="timer-project_id">Project</Label>
                        <select
                            id="timer-project_id"
                            v-model="timerForm.project_id"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            required
                        >
                            <option value="" disabled>Select a project…</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.client?.name }} — {{ project.name }}
                            </option>
                        </select>
                        <p v-if="timerForm.errors.project_id" class="text-sm text-destructive">{{ timerForm.errors.project_id }}</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="timer-notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                        <Textarea id="timer-notes" v-model="timerForm.notes" placeholder="What are you working on?" :rows="3" />
                    </div>

                    <SheetFooter>
                        <Button class="w-full" :disabled="timerForm.processing" @click="submitTimer">
                            {{ timerForm.processing ? 'Starting…' : 'Start timer' }}
                        </Button>
                    </SheetFooter>
                </TabsContent>

                <!-- Past session mode -->
                <TabsContent value="past" class="mt-5 flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label for="past-project_id">Project</Label>
                        <select
                            id="past-project_id"
                            v-model="pastForm.project_id"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            required
                        >
                            <option value="" disabled>Select a project…</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.client?.name }} — {{ project.name }}
                            </option>
                        </select>
                        <p v-if="pastForm.errors.project_id" class="text-sm text-destructive">{{ pastForm.errors.project_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <InputField label="Start" :error="pastForm.errors.started_at">
                            <Input
                                id="past-started_at"
                                v-model="pastForm.started_at"
                                type="datetime-local"
                                class="dark:[color-scheme:dark]"
                                required
                            />
                        </InputField>

                        <InputField label="End" :error="pastForm.errors.ended_at">
                            <Input id="past-ended_at" v-model="pastForm.ended_at" type="datetime-local" class="dark:[color-scheme:dark]" required />
                        </InputField>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="past-description">Description <span class="text-muted-foreground">(optional)</span></Label>
                        <Input id="past-description" v-model="pastForm.description" type="text" placeholder="e.g. Meeting with client" />
                        <p v-if="pastForm.errors.description" class="text-sm text-destructive">{{ pastForm.errors.description }}</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="past-notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                        <Textarea id="past-notes" v-model="pastForm.notes" placeholder="Additional notes…" :rows="2" />
                    </div>

                    <SheetFooter>
                        <Button class="w-full" :disabled="pastForm.processing" @click="submitPast">
                            {{ pastForm.processing ? 'Saving…' : 'Add session' }}
                        </Button>
                    </SheetFooter>
                </TabsContent>
            </Tabs>
        </SheetContent>
    </Sheet>
</template>
