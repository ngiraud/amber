<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowUpCircleIcon, CheckCircle2Icon, CircleAlertIcon, DownloadIcon, LoaderCircleIcon, RotateCcwIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { downloadProgress, installUpdate, updateInfo, updaterStatus } from '@/composables/useUpdater';

const page = usePage();
const currentVersion = computed(() => page.props.appVersion);

const chipLabel = computed(() => {
    if (updaterStatus.value === 'downloading') {
        return `${downloadProgress.value}%`;
    }

    if (updaterStatus.value === 'available' || updaterStatus.value === 'ready') {
        return updateInfo.value?.version ?? '';
    }

    return currentVersion.value;
});

const chipClass = computed(() => {
    switch (updaterStatus.value) {
        case 'available':
        case 'downloading':
            return 'text-amber-400 hover:text-amber-300';
        case 'ready':
            return 'text-green-400 hover:text-green-300';
        case 'error':
            return 'text-destructive hover:text-destructive/80';
        default:
            return 'text-muted-foreground/40 hover:text-muted-foreground/70';
    }
});

const popoverTitle = computed(() => {
    switch (updaterStatus.value) {
        case 'checking':
            return 'Checking for updates…';
        case 'available':
            return 'Update available';
        case 'downloading':
            return 'Downloading update…';
        case 'ready':
            return 'Ready to install';
        case 'up-to-date':
            return 'Up to date';
        case 'error':
            return 'Update error';
        default:
            return 'Software updates';
    }
});

const releaseNotes = computed(() => {
    const notes = updateInfo.value?.releaseNotes;

    if (!notes) {
        return null;
    }

    return Array.isArray(notes) ? notes.join('\n') : notes;
});
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                :class="['flex cursor-pointer items-center gap-1 rounded px-1.5 py-1 text-xs transition-colors', chipClass]"
                style="-webkit-app-region: no-drag"
            >
                <LoaderCircleIcon v-if="updaterStatus === 'checking'" class="size-3 animate-spin" />
                <CheckCircle2Icon v-else-if="updaterStatus === 'up-to-date'" class="size-3" />
                <ArrowUpCircleIcon v-else-if="updaterStatus === 'available'" class="size-3" />
                <DownloadIcon v-else-if="updaterStatus === 'downloading'" class="size-3" />
                <RotateCcwIcon v-else-if="updaterStatus === 'ready'" class="size-3" />
                <CircleAlertIcon v-else-if="updaterStatus === 'error'" class="size-3" />
                <span v-else class="font-mono">v{{ chipLabel }}</span>
                <span v-if="updaterStatus !== 'idle'" class="font-mono">{{ chipLabel }}</span>
            </button>
        </PopoverTrigger>

        <PopoverContent align="start" class="w-72 p-4">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-0.5">
                    <p class="text-sm font-medium">{{ popoverTitle }}</p>
                    <p class="text-xs text-muted-foreground">
                        Current version: {{ currentVersion }}
                        <template v-if="updateInfo && updaterStatus !== 'idle'"> · {{ updateInfo.version }} available </template>
                    </p>
                </div>

                <!-- Download progress -->
                <div v-if="updaterStatus === 'downloading'" class="flex flex-col gap-1.5">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-amber-400 transition-all duration-300" :style="{ width: `${downloadProgress}%` }" />
                    </div>
                    <p class="text-xs text-muted-foreground">{{ downloadProgress }}% downloaded</p>
                </div>

                <!-- Release notes -->
                <p
                    v-if="releaseNotes && updaterStatus !== 'downloading'"
                    class="max-h-32 overflow-y-auto text-xs whitespace-pre-line text-muted-foreground"
                >
                    {{ releaseNotes }}
                </p>

                <!-- Actions -->
                <div v-if="updaterStatus === 'ready'" class="flex justify-end">
                    <Button size="sm" @click="installUpdate">
                        <RotateCcwIcon class="size-3" />
                        Restart & Install
                    </Button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
