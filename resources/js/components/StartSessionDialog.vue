<script setup lang="ts">
import type { Page } from '@inertiajs/core';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import * as sessionRoutes from '@/routes/sessions';

const page = usePage();
const activeSession = computed(() => !!page.props.activeSession);
const projects = computed(() => page.props.projects ?? []);

const open = ref(false);
const mode = ref<'timer' | 'past'>('timer');

watch(open, (val) => {
    if (val) {
        mode.value = 'timer';

        router.reload({
            only: ['projects', 'activeSession'],
            onSuccess: (page: Page) => {
                if (page.props.activeSession) {
                    mode.value = 'past';
                }
            },
        });
    }
});

const { shouldOpen } = useOpenSessionDialog();
watch(shouldOpen, (val) => {
    if (val) {
        open.value = true;
        shouldOpen.value = false;
    }
});

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
        <SheetTrigger v-if="$slots.default" as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right" class="w-96">
            <SheetHeader>
                <SheetTitle>Add Session</SheetTitle>
            </SheetHeader>

            <Tabs v-model="mode" class="mt-6 px-4">
                <TabsList class="w-full">
                    <TabsTrigger value="timer" class="flex-1" :disabled="activeSession">Start now</TabsTrigger>
                    <TabsTrigger value="past" class="flex-1">Past session</TabsTrigger>
                </TabsList>

                <!-- Timer mode -->
                <TabsContent value="timer" class="mt-5 flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label for="timer-project_id">Project</Label>
                        <NativeSelect id="timer-project_id" v-model="timerForm.project_id" required>
                            <NativeSelectOption value="" disabled>Select a project…</NativeSelectOption>
                            <NativeSelectOption v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.client?.name }} — {{ project.name }}
                            </NativeSelectOption>
                        </NativeSelect>
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
                        <NativeSelect id="past-project_id" v-model="pastForm.project_id" required>
                            <NativeSelectOption value="" disabled>Select a project…</NativeSelectOption>
                            <NativeSelectOption v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.client?.name }} — {{ project.name }}
                            </NativeSelectOption>
                        </NativeSelect>
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
