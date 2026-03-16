import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useNativeMenuEvents } from '@/composables/useNativeMenuEvents';
import * as timelineRoutes from '@/routes/timeline';

export function useNativeAppEvents(): void {
    useNativeMenuEvents();

    useNativeEvent<{ eventsCount: number; period: string; since: string }>('App\\Events\\ActivityBackfillCompleted', ({ eventsCount, since }) => {
        const formattedDate = new Date(since + 'T00:00:00').toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });

        toast.success(`${eventsCount} events synced since ${formattedDate}`, {
            action: {
                label: 'Reconstruct sessions',
                onClick: () => router.visit(timelineRoutes.index({ query: { reconstruct_from: since } })),
            },
        });
    });
}
