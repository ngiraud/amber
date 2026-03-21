<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { NotebookPenIcon } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { t } from '@/composables/useTranslation';
import { update as updateSession } from '@/routes/sessions';
import type { Session } from '@/types';

const props = defineProps<{
    session: Session;
}>();

const editorRef = ref<InstanceType<typeof RichTextEditor> | null>(null);
const open = ref(false);
const notes = ref(props.session.notes ?? '');
const isSaving = ref(false);

watch(open, async (val) => {
    if (val) {
        await nextTick();
        editorRef.value?.focus();
    }
});

watch(
    () => props.session.notes,
    (value) => {
        if (!open.value) {
            notes.value = value ?? '';
        }
    },
);

function saveNotes(): void {
    isSaving.value = true;

    router.patch(
        updateSession(props.session).url,
        { notes: notes.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                toast.success(t('app.session.note_saved'));
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
}

function close(): void {
    notes.value = props.session.notes ?? '';
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>

        <DialogContent class="flex h-[calc(100svh-3rem)] flex-col gap-6 sm:max-w-full" @interact-outside.prevent @pointer-down-outside.prevent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <NotebookPenIcon class="size-4" />
                    {{ t('app.session.notes_title') }}
                </DialogTitle>
            </DialogHeader>

            <RichTextEditor ref="editorRef" v-model="notes" :placeholder="t('app.session.notes_placeholder')" class="min-h-0 flex-1" />

            <DialogFooter>
                <Button variant="outline" :disabled="isSaving" @click="close">{{ t('app.common.discard') }}</Button>
                <Button :disabled="isSaving" @click="saveNotes">
                    {{ isSaving ? t('app.common.saving') : t('app.session.save_note') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
