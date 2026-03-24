import { toast } from 'vue-sonner';
import { useDateFormat } from '@/composables/useDateFormat';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useNativeMenuEvents } from '@/composables/useNativeMenuEvents';
import { useUpdater } from '@/composables/useUpdater';

export function useNativeAppEvents(): void {
    useNativeMenuEvents();
    useUpdater();

    const { formatDateLong } = useDateFormat();

    useNativeEvent<{ eventsCount: number; sessionsCount: number; period: string; since: string }>(
        'App\\Events\\ActivityBackfillCompleted',
        ({ eventsCount, sessionsCount, since }) => {
            const formattedDate = formatDateLong(`${since}T00:00:00`);

            toast.success(`${eventsCount} events · ${sessionsCount} sessions rebuilt since ${formattedDate}`);
        },
    );
}
