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
    YoutubeIcon
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
                    class="size-7 p-0 text-destructive hover:text-white"
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
                    class="size-7 p-0 text-destructive hover:text-white"
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
                    class="size-7 p-0 text-destructive hover:text-white"
                    title="Delete table"
                    @click="editor.chain().focus().deleteTable().run()"
                >
                    <TableIcon class="size-3.5" />
                </Button>
            </template>

            <div class="mx-1 h-4 w-px bg-border" />

            <!-- YouTube -->
            <Button type="button" variant="ghost" size="sm" class="size-7 p-0" title="Insert YouTube video" @click="insertYoutube">
                <YoutubeIcon class="size-3.5" />
            </Button>
        </div>

        <EditorContent :editor="editor" class="min-h-0 flex-1 overflow-auto" />
    </div>
</template>

<style scoped>
@reference "#app.css";

:deep(.tiptap) {
    @apply h-full min-h-20 px-3 py-2 text-sm leading-5 outline-none;
}

:deep(.tiptap p) {
    @apply m-0;
}

:deep(.tiptap h1) {
    @apply mt-3 mb-1 text-xl leading-7 font-bold;
}

:deep(.tiptap h2) {
    @apply mt-2.5 mb-1 text-base leading-6 font-semibold;
}

:deep(.tiptap h3) {
    @apply mt-2 mb-1 text-[0.9375rem] leading-snug font-semibold;
}

:deep(.tiptap h1:first-child),
:deep(.tiptap h2:first-child),
:deep(.tiptap h3:first-child) {
    @apply mt-0;
}

:deep(.tiptap ul) {
    @apply my-1 list-disc pl-5;
}

:deep(.tiptap ol) {
    @apply my-1 list-decimal pl-5;
}

:deep(.tiptap strong) {
    @apply font-semibold;
}

:deep(.tiptap em) {
    @apply italic;
}

:deep(.tiptap s) {
    @apply line-through;
}

:deep(.tiptap p.is-editor-empty:first-child::before) {
    @apply pointer-events-none float-left h-0 text-muted-foreground;
    content: attr(data-placeholder);
}

:deep(.tiptap code) {
    @apply rounded bg-muted font-mono text-[0.8em];
    padding: 0.1em 0.3em;
}

:deep(.tiptap pre) {
    @apply my-2 overflow-x-auto rounded-md bg-muted px-4 py-3 font-mono text-[0.8125rem] leading-relaxed;
}

:deep(.tiptap pre code) {
    @apply bg-transparent p-0;
}

:deep(.tiptap a) {
    @apply cursor-pointer text-primary underline underline-offset-2;
}

:deep(.tiptap ul[data-type='taskList']) {
    @apply list-none pl-1;
}

:deep(.tiptap ul[data-type='taskList'] li) {
    @apply flex items-baseline gap-2;
}

:deep(.tiptap ul[data-type='taskList'] li > label) {
    @apply shrink-0;
    margin-top: 0.1rem;
}

:deep(.tiptap ul[data-type='taskList'] li > div) {
    @apply flex-1;
}

:deep(.tiptap ul[data-type='taskList'] li[data-checked='true'] > div) {
    @apply text-muted-foreground line-through;
}

:deep(.tiptap table) {
    @apply my-2 w-full table-fixed border-collapse overflow-hidden;
}

:deep(.tiptap table td),
:deep(.tiptap table th) {
    @apply relative border border-border px-2 py-1.5 align-top;
    min-width: 2rem;
}

:deep(.tiptap table th) {
    @apply bg-muted text-left font-semibold;
}

:deep(.tiptap table .selectedCell::after) {
    @apply pointer-events-none absolute inset-0 bg-accent opacity-15;
    content: '';
}

:deep(.tiptap div[data-youtube-video]) {
    @apply my-3;
}

:deep(.tiptap div[data-youtube-video] iframe) {
    @apply aspect-video w-full max-w-[1000px] rounded-md;
}
</style>
