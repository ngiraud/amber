import { onMounted, ref } from 'vue';

const SPOTLIGHT_DURATION_MS = 5000;

export function useSpotlight() {
    const activeTarget = ref<string | null>(null);

    onMounted(() => {
        const params = new URLSearchParams(window.location.search);
        const spotlight = params.get('spotlight');

        if (spotlight) {
            activeTarget.value = spotlight;

            // Remove the query param from the URL without triggering a navigation
            params.delete('spotlight');
            const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
            history.replaceState(null, '', newUrl);

            // Auto-clear after the animation completes
            setTimeout(() => {
                activeTarget.value = null;
            }, SPOTLIGHT_DURATION_MS);
        }
    });

    function spotlightClass(key: string, extraClasses = 'rounded-lg -m-2 p-2'): Record<string, boolean> {
        return { [`animate-spotlight ${extraClasses}`.trim()]: activeTarget.value === key };
    }

    return { spotlightClass };
}
