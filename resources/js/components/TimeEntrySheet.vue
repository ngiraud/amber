<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as sessionRoutes from '@/routes/sessions';
import type { Project, Session } from '@/types';

const props = defineProps<{
    date: string;
    projects: Project[];
    session?: Session;
}>();

const open = ref(false);

const isEditing = !!props.session;

const defaultStartedAt = props.session ? props.session.started_at.substring(0, 16) : `${props.date}T09:00`;

const defaultEndedAt = props.session ? (props.session.ended_at?.substring(0, 16) ?? `${props.date}T10:00`) : `${props.date}T10:00`;
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit Session' : 'Add Session' }}</SheetTitle>
            </SheetHeader>

            <Form
                :action="isEditing ? sessionRoutes.update(session!) : sessionRoutes.store()"
                :method="isEditing ? 'patch' : 'post'"
                class="mt-6 flex flex-col gap-5 px-4"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <div v-if="!isEditing" class="flex flex-col gap-2">
                    <Label for="project_id">Project</Label>
                    <select
                        id="project_id"
                        name="project_id"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        required
                    >
                        <option value="" disabled selected>Select a project…</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.client?.name }} — {{ project.name }}
                        </option>
                    </select>
                    <p v-if="errors.project_id" class="text-sm text-destructive">{{ errors.project_id }}</p>
                </div>

                <div class="flex gap-3">
                    <div class="flex flex-1 flex-col gap-2">
                        <Label for="started_at">Start</Label>
                        <Input id="started_at" name="started_at" type="datetime-local" :default-value="defaultStartedAt" required />
                        <p v-if="errors.started_at" class="text-sm text-destructive">{{ errors.started_at }}</p>
                    </div>

                    <div class="flex flex-1 flex-col gap-2">
                        <Label for="ended_at">End</Label>
                        <Input id="ended_at" name="ended_at" type="datetime-local" :default-value="defaultEndedAt" required />
                        <p v-if="errors.ended_at" class="text-sm text-destructive">{{ errors.ended_at }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="description">Description <span class="text-muted-foreground">(optional)</span></Label>
                    <Textarea
                        id="description"
                        name="description"
                        placeholder="What did you work on?"
                        rows="3"
                        :default-value="session?.description ?? undefined"
                    />
                </div>

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? 'Saving…' : isEditing ? 'Save Changes' : 'Add Session' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
