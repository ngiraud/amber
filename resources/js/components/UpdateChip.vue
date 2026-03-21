<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowUpCircleIcon, CheckCircle2Icon, CircleAlertIcon, DownloadIcon, LoaderCircleIcon, RotateCcwIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { t } from '@/composables/useTranslation';
import {
    checkGitHubRelease,
    downloadProgress,
    githubReleaseInfo,
    githubReleaseStatus,
    installUpdate,
    updateInfo,
    updaterStatus,
} from '@/composables/useUpdater';

const page = usePage();
const updaterEnabled = computed(() => page.props.updaterEnabled);
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
            return t('app.settings.updates.checking_for_updates');
        case 'available':
            return t('app.settings.updates.update_available');
        case 'downloading':
            return t('app.settings.updates.downloading_title');
        case 'ready':
            return t('app.settings.updates.ready_to_install');
        case 'up-to-date':
            return t('app.settings.updates.up_to_date_title');
        case 'error':
            return t('app.settings.updates.update_error');
        default:
            return t('app.settings.sections.software_updates');
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
                :class="[
                    'flex cursor-pointer items-center gap-1 rounded px-1.5 py-1 text-xs transition-colors',
                    updaterEnabled ? chipClass : 'text-muted-foreground/40 hover:text-muted-foreground/70',
                ]"
                style="-webkit-app-region: no-drag"
            >
                <!-- Updater enabled: state-driven icons -->
                <template v-if="updaterEnabled">
                    <LoaderCircleIcon v-if="updaterStatus === 'checking'" class="size-3 animate-spin" />
                    <CheckCircle2Icon v-else-if="updaterStatus === 'up-to-date'" class="size-3" />
                    <ArrowUpCircleIcon v-else-if="updaterStatus === 'available'" class="size-3" />
                    <DownloadIcon v-else-if="updaterStatus === 'downloading'" class="size-3" />
                    <RotateCcwIcon v-else-if="updaterStatus === 'ready'" class="size-3" />
                    <CircleAlertIcon v-else-if="updaterStatus === 'error'" class="size-3" />
                    <span v-else class="font-mono">v{{ chipLabel }}</span>
                    <span v-if="updaterStatus !== 'idle'" class="font-mono">{{ chipLabel }}</span>
                </template>

                <!-- Updater disabled: simple version + optional indicator -->
                <template v-else>
                    <ArrowUpCircleIcon v-if="githubReleaseStatus === 'available'" class="size-3 text-amber-400" />
                    <LoaderCircleIcon v-else-if="githubReleaseStatus === 'checking'" class="size-3 animate-spin" />
                    <span class="font-mono">v{{ currentVersion }}</span>
                </template>
            </button>
        </PopoverTrigger>

        <PopoverContent align="start" class="w-72 p-4">
            <!-- Updater enabled -->
            <div v-if="updaterEnabled" class="flex flex-col gap-3">
                <div class="flex flex-col gap-0.5">
                    <p class="text-sm font-medium">{{ popoverTitle }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('app.settings.updates.current_version', { version: currentVersion }) }}
                        <template v-if="updateInfo && updaterStatus !== 'idle'">
                            · {{ t('app.settings.updates.version_available', { version: updateInfo.version }) }}
                        </template>
                    </p>
                </div>

                <!-- Download progress -->
                <div v-if="updaterStatus === 'downloading'" class="flex flex-col gap-1.5">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-amber-400 transition-all duration-300" :style="{ width: `${downloadProgress}%` }" />
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('app.settings.updates.downloading', { percent: downloadProgress }) }}</p>
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
                        {{ t('app.settings.updates.restart_install') }}
                    </Button>
                </div>
            </div>

            <!-- Updater disabled: manual GitHub check -->
            <div v-else class="flex flex-col gap-3">
                <div class="flex flex-col gap-0.5">
                    <p class="text-sm font-medium">{{ t('app.settings.sections.software_updates') }}</p>
                    <p class="text-xs text-muted-foreground">{{ t('app.settings.updates.current_version', { version: currentVersion }) }}</p>
                </div>

                <p v-if="githubReleaseStatus === 'idle'" class="text-xs text-muted-foreground">
                    {{ t('app.settings.updates.check_releases_github') }}
                </p>
                <p v-else-if="githubReleaseStatus === 'checking'" class="text-xs text-muted-foreground">{{ t('app.settings.updates.checking') }}</p>
                <div v-else-if="githubReleaseStatus === 'up-to-date'" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CheckCircle2Icon class="size-3.5 text-green-500" />
                    {{ t('app.settings.updates.up_to_date') }}
                </div>
                <p v-else-if="githubReleaseStatus === 'error'" class="text-xs text-destructive">{{ t('app.settings.updates.check_failed') }}</p>
                <div v-else-if="githubReleaseStatus === 'available' && githubReleaseInfo" class="flex items-center justify-between gap-3">
                    <p class="text-xs font-medium">{{ t('app.settings.updates.version_available', { version: githubReleaseInfo.version }) }}</p>
                    <Button size="sm" variant="outline" as="a" :href="githubReleaseInfo.url" target="_blank">
                        {{ t('app.settings.updates.view_release') }}
                    </Button>
                </div>

                <Button size="sm" variant="outline" :disabled="githubReleaseStatus === 'checking'" @click="checkGitHubRelease(currentVersion)">
                    <LoaderCircleIcon v-if="githubReleaseStatus === 'checking'" class="size-3 animate-spin" />
                    <ArrowUpCircleIcon v-else class="size-3" />
                    {{ t('app.settings.updates.check') }}
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
