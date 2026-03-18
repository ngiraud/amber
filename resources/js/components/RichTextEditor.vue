<script setup lang="ts">
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import Typography from '@tiptap/extension-typography';
import Youtube from '@tiptap/extension-youtube';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { common, createLowlight } from 'lowlight';
import {
    ArrowDownToLineIcon,
    ArrowLeftToLineIcon,
    ArrowRightToLineIcon,
    ArrowUpToLineIcon,
    BoldIcon,
    CheckSquareIcon,
    CodeIcon,
    Columns2Icon,
    Heading1Icon,
    Heading2Icon,
    Heading3Icon,
    ItalicIcon,
    Link2Icon,
    Link2OffIcon,
    ListIcon,
    ListOrderedIcon,
    Rows2Icon,
    StrikethroughIcon,
    TableIcon,
    Trash2Icon,
    YoutubeIcon,
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
        Youtube.configure({ nocookie: true }),
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

function insertYoutube(): void {
    if (!editor.value) {
        return;
    }

    const url = window.prompt('YouTube URL');

    if (!url) {
        return;
    }

    editor.value.chain().focus().setYoutubeVideo({ src: url }).run();
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

            <!-- Table (insert) -->
            <Button
                v-if="!editor.isActive('table')"
                type="button"
                variant="ghost"
                size="sm"
                class="size-7 p-0"
                title="Insert table"
                @click="insertTable"
            >
                <TableIcon class="size-3.5" />
            </Button>

            <!-- Table controls (shown when cursor is inside a table) -->
            <template v-if="editor.isActive('table')">
                <div class="mx-1 h-4 w-px bg-border" />
                <Rows2Icon class="mx-0.5 size-3.5 shrink-0 text-muted-foreground" />
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0"
                    title="Add row above"
                    @click="editor.chain().focus().addRowBefore().run()"
                >
                    <ArrowUpToLineIcon class="size-3.5" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0"
                    title="Add row below"
                    @click="editor.chain().focus().addRowAfter().run()"
                >
                    <ArrowDownToLineIcon class="size-3.5" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0 text-destructive hover:text-destructive"
                    title="Delete row"
                    @click="editor.chain().focus().deleteRow().run()"
                >
                    <Trash2Icon class="size-3.5" />
                </Button>
                <div class="mx-1 h-4 w-px bg-border" />
                <Columns2Icon class="mx-0.5 size-3.5 shrink-0 text-muted-foreground" />
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0"
                    title="Add column before"
                    @click="editor.chain().focus().addColumnBefore().run()"
                >
                    <ArrowLeftToLineIcon class="size-3.5" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0"
                    title="Add column after"
                    @click="editor.chain().focus().addColumnAfter().run()"
                >
                    <ArrowRightToLineIcon class="size-3.5" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0 text-destructive hover:text-destructive"
                    title="Delete column"
                    @click="editor.chain().focus().deleteColumn().run()"
                >
                    <Trash2Icon class="size-3.5" />
                </Button>
                <div class="mx-1 h-4 w-px bg-border" />
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0 text-destructive hover:text-destructive"
                    title="Delete table"
                    @click="editor.chain().focus().deleteTable().run()"
                >
                    <TableIcon class="size-3.5" />
                </Button>
            </template>

            <div class="mx-1 h-4 w-px bg-border" />

            <!-- YouTube -->
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-7 p-0"
                title="Insert YouTube video"
                @click="insertYoutube"
            >
                <YoutubeIcon class="size-3.5" />
            </Button>
        </div>

        <EditorContent :editor="editor" class="min-h-0 flex-1 overflow-auto" />
    </div>
</template>
