import { toast } from 'vue-sonner';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useNativeMenuEvents } from '@/composables/useNativeMenuEvents';

export function useNativeAppEvents(): void {
    useNativeMenuEvents();

    useNativeEvent<{ eventsCount: number; period: string }>('App\\Events\\ActivityBackfillCompleted', ({ eventsCount, period }) => {
        toast.success(`Synced ${eventsCount} events from the last ${period}`);
    });
}
