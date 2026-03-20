import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useNativeEvent } from '@/composables/useNativeEvent';
import * as updateRoutes from '@/routes/settings/updates';

export type UpdaterStatus = 'idle' | 'checking' | 'up-to-date' | 'available' | 'downloading' | 'ready' | 'error';
export type GitHubReleaseStatus = 'idle' | 'checking' | 'up-to-date' | 'available' | 'error';

export type GitHubReleaseInfo = {
    version: string;
    url: string;
    publishedAt: string;
};

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

// GitHub release check (for when auto-updater is disabled)
export const githubReleaseStatus = ref<GitHubReleaseStatus>('idle');
export const githubReleaseInfo = ref<GitHubReleaseInfo | null>(null);

export async function checkGitHubRelease(currentVersion: string): Promise<void> {
    githubReleaseStatus.value = 'checking';

    try {
        const response = await fetch('https://api.github.com/repos/ngiraud/amber/releases/latest', {
            headers: { Accept: 'application/vnd.github.v3+json', 'User-Agent': 'Amber-App' },
        });

        if (!response.ok) {
            githubReleaseStatus.value = 'error';

            return;
        }

        const data = await response.json();
        const latestVersion = (data.tag_name ?? '').replace(/^v/, '');

        githubReleaseInfo.value = { version: latestVersion, url: data.html_url, publishedAt: data.published_at };
        githubReleaseStatus.value = latestVersion && latestVersion !== currentVersion ? 'available' : 'up-to-date';
    } catch {
        githubReleaseStatus.value = 'error';
    }
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
