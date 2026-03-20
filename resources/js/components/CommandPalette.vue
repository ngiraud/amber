<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    ActivityIcon,
    ArrowUpCircleIcon,
    CalendarDaysIcon,
    ClockIcon,
    FileTextIcon,
    FolderIcon,
    LayoutDashboardIcon,
    PlayIcon,
    SettingsIcon,
    SquareIcon,
    UsersIcon,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
    CommandShortcut,
} from '@/components/ui/command';
import { Kbd, KbdGroup } from '@/components/ui/kbd';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { useOpenClientSheet } from '@/composables/useOpenClientSheet';
import { useOpenProjectSheet } from '@/composables/useOpenProjectSheet';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import { formatHotkey } from '@/composables/useOs';
import { checkForUpdates, checkGitHubRelease } from '@/composables/useUpdater';
import { home } from '@/routes';
import * as activityRoutes from '@/routes/activity';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import * as reportRoutes from '@/routes/reports';
import * as sessionRoutes from '@/routes/sessions';
import * as settingsRoutes from '@/routes/settings';
import * as timelineRoutes from '@/routes/timeline';

const { isOpen } = useCommandPalette();
const { shouldOpen: shouldOpenSession } = useOpenSessionDialog();
const { shouldOpen: shouldOpenClientSheet } = useOpenClientSheet();
const { shouldOpen: shouldOpenProjectSheet } = useOpenProjectSheet();

const page = usePage();
const activeSession = computed(() => page.props.activeSession);
const hotkeys = computed(() => page.props.hotkeys);
const appVersion = computed(() => page.props.appVersion);
const updaterEnabled = computed(() => page.props.updaterEnabled);

function hotkey(label: string): string {
    const item = hotkeys.value.find((h) => h.label === label);

    return item ? formatHotkey(item.value) : '';
}

function onKeyDown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        isOpen.value = !isOpen.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => window.removeEventListener('keydown', onKeyDown));

function navigate(url: string) {
    router.visit(url, { onSuccess: () => close() });
}

function startSession() {
    shouldOpenSession.value = true;
    close();
}

function stopSession() {
    if (!activeSession.value) {
        return;
    }

    router.visit(sessionRoutes.stop(activeSession.value).url, { method: 'patch', onSuccess: () => close() });
}

function newClient() {
    shouldOpenClientSheet.value = true;
    close();
    router.visit(clientRoutes.index().url);
}

function newProject() {
    shouldOpenProjectSheet.value = true;
    close();
    router.visit(projectRoutes.index().url);
}

function close() {
    isOpen.value = false;
}

function triggerUpdateCheck() {
    if (updaterEnabled.value) {
        checkForUpdates();
    } else {
        checkGitHubRelease(appVersion.value);
    }

    navigate(settingsRoutes.general().url);
}
</script>

<template>
    <CommandDialog v-model:open="isOpen">
        <CommandInput placeholder="Search..." />
        <CommandList>
            <CommandEmpty>No results found.</CommandEmpty>

            <CommandGroup heading="Navigate">
                <CommandItem value="dashboard" @select="navigate(home().url)">
                    <LayoutDashboardIcon />
                    Dashboard
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Dashboard') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="timeline" @select="navigate(timelineRoutes.index().url)">
                    <CalendarDaysIcon />
                    Timeline
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Timeline') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="clients" @select="navigate(clientRoutes.index().url)">
                    <UsersIcon />
                    Clients
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Clients') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="projects" @select="navigate(projectRoutes.index().url)">
                    <FolderIcon />
                    Projects
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Projects') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="reports" @select="navigate(reportRoutes.index().url)">
                    <FileTextIcon />
                    Reports
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Reports') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="sessions" @select="navigate(sessionRoutes.index().url)">
                    <ClockIcon />
                    Sessions
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Sessions') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="activity" @select="navigate(activityRoutes.index().url)">
                    <ActivityIcon />
                    Activity
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Activity') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
            </CommandGroup>

            <CommandSeparator />

            <CommandGroup heading="Sessions">
                <CommandItem value="start session" @select="startSession">
                    <PlayIcon />
                    Start session
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Toggle Session') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem v-if="activeSession" value="stop session" @select="stopSession">
                    <SquareIcon />
                    Stop session
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Toggle Session') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
            </CommandGroup>

            <CommandSeparator />

            <CommandGroup heading="Create">
                <CommandItem value="new project" @select="newProject">
                    <FolderIcon />
                    New project
                    <CommandShortcut>
                        <Kbd>{{ hotkey('New Project') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="new client" @select="newClient">
                    <UsersIcon />
                    New client
                    <CommandShortcut>
                        <Kbd>{{ hotkey('New Client') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
            </CommandGroup>

            <CommandSeparator />

            <CommandGroup heading="Settings">
                <CommandItem value="check for updates" @select="triggerUpdateCheck">
                    <ArrowUpCircleIcon />
                    Check for updates
                </CommandItem>
                <CommandItem value="general settings" @select="navigate(settingsRoutes.general().url)">
                    <SettingsIcon />
                    General
                    <CommandShortcut>
                        <Kbd>{{ hotkey('Settings') }}</Kbd>
                    </CommandShortcut>
                </CommandItem>
                <CommandItem value="activity settings" @select="navigate(settingsRoutes.activity().url)">
                    <SettingsIcon />
                    Activity
                </CommandItem>
                <CommandItem value="sources settings" @select="navigate(settingsRoutes.sources().url)">
                    <SettingsIcon />
                    Sources
                </CommandItem>
                <CommandItem value="ai settings" @select="navigate(settingsRoutes.ai().url)">
                    <SettingsIcon />
                    AI
                </CommandItem>
            </CommandGroup>
        </CommandList>

        <div class="flex items-center gap-4 border-t px-4 py-2.5">
            <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <KbdGroup><Kbd>↑</Kbd><Kbd>↓</Kbd></KbdGroup>
                Navigate
            </span>
            <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Kbd>↵</Kbd>
                Select
            </span>
            <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Kbd>esc</Kbd>
                Close
            </span>
        </div>
    </CommandDialog>
</template>
