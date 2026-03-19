import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useNativeEvent } from '@/composables/useNativeEvent';
import * as updateRoutes from '@/routes/settings/updates';

export type UpdaterStatus = 'idle' | 'checking' | 'up-to-date' | 'available' | 'downloading' | 'ready' | 'error';

export type UpdateInfo = {
    version: string;
    releaseDate: string;
    releaseName: string | null;
    releaseNotes: string | string[] | null;
};

// Module-level shared state
export const updaterStatus = ref<UpdaterStatus>('idle');
export const updateInfo = ref<UpdateInfo | null>(null);
export const downloadProgress = ref(0);
export const updaterError = ref<string | null>(null);

export function checkForUpdates(): void {
    router.post(updateRoutes.check().url, {}, { preserveState: true, preserveScroll: true });
}

export function installUpdate(): void {
    router.post(updateRoutes.install().url, {}, { preserveState: true, preserveScroll: true });
}

export function dismissUpdate(): void {
    updaterStatus.value = 'idle';
    updateInfo.value = null;
    downloadProgress.value = 0;
    updaterError.value = null;
}

/**
 * Register NativePHP AutoUpdater event listeners.
 * Call once from the root layout via useNativeAppEvents().
 */
export function useUpdater(): void {
    useNativeEvent('Native\\Desktop\\Events\\AutoUpdater\\CheckingForUpdate', () => {
        updaterStatus.value = 'checking';
    });

    useNativeEvent<UpdateInfo>('Native\\Desktop\\Events\\AutoUpdater\\UpdateAvailable', (payload) => {
        updaterStatus.value = 'available';
        updateInfo.value = payload;
    });

    useNativeEvent('Native\\Desktop\\Events\\AutoUpdater\\UpdateNotAvailable', () => {
        updaterStatus.value = 'up-to-date';
    });

    useNativeEvent<{ percent: number }>('Native\\Desktop\\Events\\AutoUpdater\\DownloadProgress', ({ percent }) => {
        updaterStatus.value = 'downloading';
        downloadProgress.value = Math.round(percent);
    });

    useNativeEvent<UpdateInfo>('Native\\Desktop\\Events\\AutoUpdater\\UpdateDownloaded', (payload) => {
        updaterStatus.value = 'ready';
        updateInfo.value = payload;
        downloadProgress.value = 100;
    });

    useNativeEvent<{ message: string }>('Native\\Desktop\\Events\\AutoUpdater\\Error', ({ message }) => {
        updaterStatus.value = 'error';
        updaterError.value = message;
    });

    useNativeEvent('Native\\Desktop\\Events\\AutoUpdater\\UpdateCancelled', () => {
        updaterStatus.value = 'idle';
    });
}
