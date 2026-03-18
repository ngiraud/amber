<script setup lang="ts">
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { BoldIcon, ItalicIcon, ListIcon, ListOrderedIcon, StrikethroughIcon } from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        placeholder?: string;
        editable?: boolean;
    }>(),
    {
        editable: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue ?? '',
    editable: true,
    extensions: [StarterKit, Placeholder.configure({ placeholder: props.placeholder })],
    onUpdate: ({ editor: e }) => {
        emit('update:modelValue', e.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && editor.value.getHTML() !== (value ?? '')) {
            editor.value.commands.setContent(value ?? '');
        }
    },
);

watch(
    () => props.editable,
    (value) => {
        editor.value?.setEditable(value);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

defineExpose({
    focus: () => editor.value?.commands.focus('end'),
});
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow]',
                !editable ? 'border-transparent shadow-none' : '',
            )
        "
    >
        <div v-if="editable && editor" class="flex items-center gap-0.5 border-b border-input px-2 py-1">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('bold') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <BoldIcon class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('italic') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <ItalicIcon class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('strike') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <StrikethroughIcon class="size-3.5" />
            </Button>
            <div class="mx-1 h-4 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('bulletList') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <ListIcon class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('orderedList') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrderedIcon class="size-3.5" />
            </Button>
        </div>

        <EditorContent :editor="editor" class="min-h-0 flex-1 overflow-auto" />
    </div>
</template>
