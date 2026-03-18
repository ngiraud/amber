<script setup lang="ts">
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import Typography from '@tiptap/extension-typography';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { common, createLowlight } from 'lowlight';
import {
    BoldIcon,
    CheckSquareIcon,
    CodeIcon,
    Heading1Icon,
    Heading2Icon,
    Heading3Icon,
    ItalicIcon,
    Link2Icon,
    Link2OffIcon,
    ListIcon,
    ListOrderedIcon,
    StrikethroughIcon,
    TableIcon
} from 'lucide-vue-next';
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

const lowlight = createLowlight(common);

const editor = useEditor({
    content: props.modelValue ?? '',
    editable: true,
    extensions: [
        StarterKit.configure({ codeBlock: false }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Typography,
        Link.configure({ openOnClick: false }),
        TaskList,
        TaskItem.configure({ nested: true }),
        CodeBlockLowlight.configure({ lowlight }),
        TableKit,
    ],
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

function setLink(): void {
    if (!editor.value) {
        return;
    }

    const previousUrl = editor.value.getAttributes('link').href as string | undefined;
    const url = window.prompt('URL', previousUrl);

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function insertTable(): void {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
}
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
        <div v-if="editable && editor" class="flex flex-wrap items-center gap-0.5 border-b border-input px-2 py-1">
            <!-- Headings -->
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('heading', { level: 1 }) ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
            >
                <Heading1Icon class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('heading', { level: 2 }) ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2Icon class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('heading', { level: 3 }) ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3Icon class="size-3.5" />
            </Button>

            <div class="mx-1 h-4 w-px bg-border" />

            <!-- Text formatting -->
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
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('code') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleCode().run()"
            >
                <CodeIcon class="size-3.5" />
            </Button>

            <div class="mx-1 h-4 w-px bg-border" />

            <!-- Lists -->
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
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('taskList') ? 'bg-accent text-accent-foreground' : '')"
                @click="editor.chain().focus().toggleTaskList().run()"
            >
                <CheckSquareIcon class="size-3.5" />
            </Button>

            <div class="mx-1 h-4 w-px bg-border" />

            <!-- Link -->
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('link') ? 'bg-accent text-accent-foreground' : '')"
                @click="setLink"
            >
                <Link2Icon class="size-3.5" />
            </Button>
            <Button
                v-if="editor.isActive('link')"
                type="button"
                variant="ghost"
                size="sm"
                class="size-7 p-0"
                @click="editor.chain().focus().unsetLink().run()"
            >
                <Link2OffIcon class="size-3.5" />
            </Button>

            <!-- Table -->
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="cn('size-7 p-0', editor.isActive('table') ? 'bg-accent text-accent-foreground' : '')"
                @click="insertTable"
            >
                <TableIcon class="size-3.5" />
            </Button>
        </div>

        <EditorContent :editor="editor" class="min-h-0 flex-1 overflow-auto" />
    </div>
</template>
