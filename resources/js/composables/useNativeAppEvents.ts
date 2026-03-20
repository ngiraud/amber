import { toast } from 'vue-sonner';
import { useDateFormat } from '@/composables/useDateFormat';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useNativeMenuEvents } from '@/composables/useNativeMenuEvents';
import { useReconstructFromDialog } from '@/composables/useReconstructFromDialog';
import { useUpdater } from '@/composables/useUpdater';

export function useNativeAppEvents(): void {
    useNativeMenuEvents();
    useUpdater();

    const { formatDateLong } = useDateFormat();
    const { show: showReconstructDialog } = useReconstructFromDialog();

    useNativeEvent<{ eventsCount: number; period: string; since: string }>('App\\Events\\ActivityBackfillCompleted', ({ eventsCount, since }) => {
        const formattedDate = formatDateLong(`${since}T00:00:00`);

        toast.success(`${eventsCount} events synced since ${formattedDate}`, {
            action: {
                label: 'Reconstruct sessions',
                onClick: () => showReconstructDialog(since),
            },
        });
    });
}
