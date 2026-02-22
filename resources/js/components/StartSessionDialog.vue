<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as sessionRoutes from '@/routes/sessions';
import type { Project } from '@/types';

defineProps<{
    projects: Project[];
}>();

const open = ref(false);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right" class="w-96">
            <SheetHeader>
                <SheetTitle>Start Session</SheetTitle>
            </SheetHeader>

            <Form
                :action="sessionRoutes.store()"
                class="mt-6 flex flex-col gap-5 px-4"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <div class="flex flex-col gap-2">
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

                <div class="flex flex-col gap-2">
                    <Label for="notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                    <Textarea id="notes" name="notes" placeholder="What are you working on?" rows="3" />
                </div>

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? 'Starting…' : 'Start Session' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
