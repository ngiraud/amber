<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { t } from '@/composables/useTranslation';
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
                <DialogTitle>{{ project.is_active ? t('app.project.archive_title') : t('app.project.restore_title') }}</DialogTitle>
                <DialogDescription>
                    <template v-if="project.is_active">
                        {{ t('app.project.archive_description', { name: project.name }) }}
                    </template>
                    <template v-else>
                        {{ t('app.project.restore_description', { name: project.name }) }}
                    </template>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline">{{ t('app.common.cancel') }}</Button>
                </DialogClose>
                <Form :action="projectRoutes.toggleStatus(project)" @success="open = false">
                    <Button type="submit">
                        {{ project.is_active ? t('app.common.archive') : t('app.common.restore') }}
                    </Button>
                </Form>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
