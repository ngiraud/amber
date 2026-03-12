<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import * as projectRoutes from '@/routes/projects';
import type { Project } from '@/types';

defineProps<{
    project: Project;
}>();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ project.is_active ? 'Archive project' : 'Restore project' }}</DialogTitle>
                <DialogDescription>
                    <template v-if="project.is_active">
                        Archiving <strong>{{ project.name }}</strong> will hide it from active lists and stop new activity from being recorded. Existing
                        data is preserved.
                    </template>
                    <template v-else>
                        Restoring <strong>{{ project.name }}</strong> will make it active again and allow new activity to be recorded.
                    </template>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline">Cancel</Button>
                </DialogClose>
                <Form :action="projectRoutes.toggleStatus(project)" @success="open = false">
                    <Button type="submit">
                        {{ project.is_active ? 'Archive' : 'Restore' }}
                    </Button>
                </Form>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
