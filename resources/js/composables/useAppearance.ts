import { router, usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import type { Appearance } from '@/types';
import type { GeneralSettings } from '@/types/settings';

let activeTheme: Appearance = 'system';

export function applyTheme(value: Appearance): void {
    activeTheme = value;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = value === 'dark' || (value === 'system' && prefersDark);

    document.documentElement.classList.toggle('dark', isDark);
}

/**
 * Global initialization for the theme.
 * Called once in app.ts — runs outside any component, so usePage() cannot be used here.
 */
export function initializeTheme(): void {
    // Apply system preference immediately; Inertia props aren't available yet.
    applyTheme('system');

    // Re-evaluate when the OS preference changes (only relevant when theme is 'system').
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (activeTheme === 'system') {
            applyTheme('system');
        }
    });

    // Apply the saved theme from Inertia page props on every navigation.
    router.on('navigate', (event) => {
        const settings = event.detail.page.props.generalSettings as GeneralSettings | undefined;
        applyTheme(settings?.theme || 'system');
    });
}

/**
 * Composable for theme management within components.
 * Syncs the theme when props change (e.g. after saving appearance settings).
 */
export function useAppearance() {
    const page = usePage();

    onMounted(() => {
        const theme = (page.props.generalSettings?.theme as Appearance) || 'system';
        applyTheme(theme);
    });

    watch(
        () => page.props.generalSettings?.theme as Appearance,
        (theme) => applyTheme(theme || 'system'),
    );
}
