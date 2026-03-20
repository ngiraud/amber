import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { useDateFormat } from '@/composables/useDateFormat';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useNativeMenuEvents } from '@/composables/useNativeMenuEvents';
import { useUpdater } from '@/composables/useUpdater';
import * as timelineRoutes from '@/routes/timeline';

export function useNativeAppEvents(): void {
    useNativeMenuEvents();
    useUpdater();

    const { formatDateLong } = useDateFormat();

    useNativeEvent<{ eventsCount: number; period: string; since: string }>('App\\Events\\ActivityBackfillCompleted', ({ eventsCount, since }) => {
        const formattedDate = formatDateLong(`${since}T00:00:00`);

        toast.success(`${eventsCount} events synced since ${formattedDate}`, {
            action: {
                label: 'Reconstruct sessions',
                onClick: () => router.visit(timelineRoutes.index({ query: { reconstruct_from: since } })),
            },
        });
    });
}
