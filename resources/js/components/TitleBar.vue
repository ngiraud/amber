<script setup lang="ts">
import { Form, router, usePage } from '@inertiajs/vue3';
import { NotebookPenIcon, SquareIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import SessionTimer from '@/components/SessionTimer.vue';
import { Button } from '@/components/ui/button';
import { Kbd } from '@/components/ui/kbd';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { formatHotkey } from '@/composables/useOs';
import * as sessionRoutes from '@/routes/sessions';

defineProps<{
    title?: string;
    breadcrumb?: string[];
}>();

const { isOpen: commandPaletteOpen } = useCommandPalette();

useNativeEvent('App\\Events\\SessionStarted', () => router.reload({ only: ['activeSession'] }));
useNativeEvent('App\\Events\\SessionStopped', () => router.reload({ only: ['activeSession'] }));

const page = usePage();
const activeSession = computed(() => page.props.activeSession);

const notesOpen = ref(false);
const notes = ref(activeSession.value?.notes ?? '');

watch(
    () => activeSession.value?.id,
    () => {
        notes.value = activeSession.value?.notes ?? '';
    },
);

function saveNotes() {
    if (!activeSession.value) {
        return;
    }

    router.patch(
        sessionRoutes.update(activeSession.value).url,
        { notes: notes.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                notesOpen.value = false;
                toast.success('Note saved.');
            },
        },
    );
}
</script>

<template>
    <div class="relative flex h-9 w-full shrink-0 items-center bg-sidebar select-none" style="-webkit-app-region: drag">
        <!-- Left: spacer for macOS traffic lights -->
        <div class="w-[70px] shrink-0" />

        <!-- Left: spacer for macOS traffic lights + command palette trigger -->
        <div class="ml-3 flex shrink-0 items-center justify-end pr-1" style="-webkit-app-region: no-drag">
            <button
                class="flex cursor-pointer items-center gap-1.5 rounded px-2 py-1 text-xs text-muted-foreground/60 transition-colors hover:text-muted-foreground"
                @click="commandPaletteOpen = true"
            >
                <span>Use</span>
                <Kbd class="opacity-60">{{ formatHotkey('CmdOrCtrl+K') }}</Kbd>
                <span>to search</span>
            </button>
        </div>

        <!-- Center: always shows breadcrumb/title -->
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div v-if="breadcrumb?.length" class="flex items-center gap-1.5">
                <template v-for="(item, index) in breadcrumb" :key="index">
                    <span v-if="index > 0" class="text-xs text-muted-foreground/40">›</span>
                    <span :class="['text-xs', index < breadcrumb.length - 1 ? 'text-muted-foreground' : 'font-medium text-foreground/80']">{{
                        item
                    }}</span>
                </template>
            </div>
            <span v-else class="text-xs font-medium text-muted-foreground">
                {{ title ?? 'Activity Record' }}
            </span>
        </div>

        <!-- Right: session info when active -->
        <div v-if="activeSession" class="ml-auto flex shrink-0 items-center justify-end gap-2 pr-3" style="-webkit-app-region: no-drag">
            <div class="flex items-center justify-end gap-2">
                <span class="size-1.5 shrink-0 animate-pulse rounded-full bg-green-500" />
                <span class="max-w-[160px] truncate text-xs text-muted-foreground">
                    {{ activeSession.project?.client?.name
                    }}<span v-if="activeSession.project?.client && activeSession.project?.name"> — {{ activeSession.project.name }}</span>
                </span>
                <span class="shrink-0 font-mono text-xs text-muted-foreground tabular-nums">
                    <SessionTimer :started-at="activeSession.started_at" />
                </span>
            </div>

            <div class="flex items-center justify-end">
                <Popover v-model:open="notesOpen">
                    <PopoverTrigger as-child>
                        <Button variant="ghost" size="sm" class="h-6 text-xs text-muted-foreground hover:text-white">
                            <NotebookPenIcon class="size-3" />
                            Note
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-64 p-3" align="end">
                        <Textarea
                            v-model="notes"
                            placeholder="Add a note..."
                            class="min-h-20 resize-none text-xs"
                            @keydown.ctrl.enter="saveNotes"
                            @keydown.meta.enter="saveNotes"
                        />
                        <Button size="sm" class="mt-2 w-full" @click="saveNotes">Save note</Button>
                    </PopoverContent>
                </Popover>

                <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
                    <Button variant="ghost" size="sm" class="h-6 text-xs text-muted-foreground hover:text-white" @click="submit">
                        <SquareIcon class="size-3 fill-current" />
                        Stop
                    </Button>
                </Form>
            </div>
        </div>
    </div>
</template>
