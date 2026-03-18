<script setup lang="ts">
import { Form, router, usePage } from '@inertiajs/vue3';
import { ClockIcon, FolderPlusIcon, NotebookPenIcon, PlayIcon, PlusIcon, SquareIcon, UserPlusIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import LogPastSessionSheet from '@/components/LogPastSessionSheet.vue';
import SessionNotesDialog from '@/components/SessionNotesDialog.vue';
import SessionTimer from '@/components/SessionTimer.vue';
import StartTimerSheet from '@/components/StartTimerSheet.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuShortcut, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Kbd } from '@/components/ui/kbd';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useOpenClientSheet } from '@/composables/useOpenClientSheet';
import { useOpenProjectSheet } from '@/composables/useOpenProjectSheet';
import { formatHotkey } from '@/composables/useOs';
import * as sessionRoutes from '@/routes/sessions';

defineProps<{
    title?: string;
    breadcrumb?: string[];
}>();

const { isOpen: commandPaletteOpen } = useCommandPalette();
const { shouldOpen: clientSheetOpen } = useOpenClientSheet();
const { shouldOpen: projectSheetOpen } = useOpenProjectSheet();
const logPastSessionOpen = ref(false);

useNativeEvent('App\\Events\\SessionStarted', () => router.reload({ only: ['activeSession', 'currentActivity'] }));
useNativeEvent('App\\Events\\SessionStopped', () => router.reload({ only: ['activeSession', 'currentActivity'] }));

const page = usePage();
const activeSession = computed(() => page.props.activeSession);
const currentActivity = computed(() => page.props.currentActivity);
const hotkeys = computed(() => page.props.hotkeys ?? []);

const currentSince = computed(() => {
    if (!currentActivity.value?.length) {
        return null;
    }

    return currentActivity.value.reduce((earliest, item) => (item.since < earliest ? item.since : earliest), currentActivity.value[0].since);
});

function formatElapsed(since: string): string {
    const totalSeconds = Math.floor((Date.now() - new Date(since).getTime()) / 1000);
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;

    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

const currentElapsed = ref('');
const currentElapsedMap = ref<Record<string, string>>({});

function tickCurrentTimers(): void {
    const activity = currentActivity.value;

    if (!activity?.length) {
        currentElapsed.value = '';
        currentElapsedMap.value = {};

        return;
    }

    if (currentSince.value) {
        currentElapsed.value = formatElapsed(currentSince.value);
    }

    const map: Record<string, string> = {};

    for (const item of activity) {
        map[item.project.id] = formatElapsed(item.since);
    }

    currentElapsedMap.value = map;
}

let timerInterval: ReturnType<typeof setInterval> | null = null;
let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    tickCurrentTimers();
    timerInterval = setInterval(tickCurrentTimers, 1000);
    pollInterval = setInterval(() => {
        router.reload({ only: ['currentActivity'] });
    }, 60 * 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }

    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

function hotkey(label: string): string {
    const item = hotkeys.value.find((h) => h.label === label);

    return item ? formatHotkey(item.value) : '';
}

</script>

<template>
    <div class="relative flex h-9 w-full shrink-0 items-center bg-sidebar select-none" style="-webkit-app-region: drag">
        <!-- Left: spacer for macOS traffic lights -->
        <div class="w-[70px] shrink-0" />

        <!-- Left: command palette trigger -->
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

        <!-- Center: breadcrumb/title -->
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

        <!-- Right: controls -->
        <div class="ml-auto flex shrink-0 items-center justify-end gap-2 pr-3" style="-webkit-app-region: no-drag">
            <template v-if="activeSession">
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
                    <SessionNotesDialog :session="activeSession">
                        <Button variant="ghost" size="xs" class="text-muted-foreground hover:text-white">
                            <NotebookPenIcon class="size-3" />
                            Note
                        </Button>
                    </SessionNotesDialog>

                    <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
                        <Button variant="ghost" size="xs" class="text-muted-foreground hover:text-white" @click="submit">
                            <SquareIcon class="size-3 fill-current" />
                            Stop
                            <Kbd class="opacity-60">{{ hotkey('Toggle Session') }}</Kbd>
                        </Button>
                    </Form>
                </div>
            </template>

            <template v-else>
                <!-- Current activity indicator -->
                <template v-if="currentActivity?.length">
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                size="xs"
                                variant="ghost"
                                class="border border-border/40 font-normal text-muted-foreground hover:border-border/70 hover:text-white"
                                style="-webkit-app-region: no-drag"
                            >
                                <span class="size-1.5 shrink-0 animate-pulse rounded-full bg-green-500/50" />
                                <span class="truncate">
                                    Working on {{ currentActivity.length }} {{ currentActivity.length > 1 ? 'projects' : 'project' }}
                                </span>
                                <span class="text-muted-foreground/30">·</span>
                                <span v-if="currentElapsed" class="shrink-0 font-mono tabular-nums">
                                    {{ currentElapsed }}
                                </span>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent align="end" class="max-h-64 max-w-72 min-w-56 overflow-y-auto p-1.5">
                            <div class="flex flex-col">
                                <div v-for="item in currentActivity" :key="item.project.id" class="flex items-center gap-2.5 rounded px-2 py-1.5">
                                    <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: item.project.color }" />
                                    <span class="min-w-0 flex-1 truncate text-xs text-foreground/80">
                                        {{ item.project.client?.name }} — {{ item.project.name }}
                                    </span>
                                    <span class="shrink-0 font-mono text-xs text-muted-foreground tabular-nums">
                                        {{ currentElapsedMap[item.project.id] }}
                                    </span>
                                </div>
                            </div>
                        </PopoverContent>
                    </Popover>
                </template>

                <StartTimerSheet>
                    <Button variant="ghost" size="xs" class="text-muted-foreground/50 hover:bg-accent/70 hover:text-white">
                        <PlayIcon class="size-3" />
                        Live session
                        <Kbd class="opacity-60">{{ hotkey('Toggle Session') }}</Kbd>
                    </Button>
                </StartTimerSheet>
            </template>

            <LogPastSessionSheet v-model:open="logPastSessionOpen" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-xs" class="text-muted-foreground/50 hover:text-white">
                        <PlusIcon class="size-3" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-52 text-xs">
                    <DropdownMenuItem class="text-xs" @click="logPastSessionOpen = true">
                        <ClockIcon class="size-3" />
                        Log past session
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="text-xs" @click="clientSheetOpen = true">
                        <UserPlusIcon class="size-3" />
                        New client
                        <DropdownMenuShortcut>
                            <Kbd>{{ hotkey('New Client') }}</Kbd>
                        </DropdownMenuShortcut>
                    </DropdownMenuItem>
                    <DropdownMenuItem class="text-xs" @click="projectSheetOpen = true">
                        <FolderPlusIcon class="size-3" />
                        New project
                        <DropdownMenuShortcut>
                            <Kbd>{{ hotkey('New Project') }}</Kbd>
                        </DropdownMenuShortcut>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
