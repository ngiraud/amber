import { router } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useNativeEvent } from '@/composables/useNativeEvent';
import { useOpenClientSheet } from '@/composables/useOpenClientSheet';
import { useOpenProjectSheet } from '@/composables/useOpenProjectSheet';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';

export function useNativeMenuEvents() {
    const { shouldOpen: sessionDialogOpen } = useOpenSessionDialog();
    const { shouldOpen: clientSheetOpen } = useOpenClientSheet();
    const { shouldOpen: projectSheetOpen } = useOpenProjectSheet();

    const { isCurrentUrl } = useCurrentUrl();

    useNativeEvent('App\\Events\\Native\\OpenStartSessionFromMenu', () => {
        sessionDialogOpen.value = true;
    });

    useNativeEvent<{ url: string }>('App\\Events\\Native\\NavigateToPage', ({ url }) => {
        router.visit(url);
    });

    useNativeEvent('App\\Events\\Native\\OpenCreateClientFromMenu', () => {
        clientSheetOpen.value = true;

        if (isCurrentUrl(clientRoutes.index().url)) {
            return;
        }

        router.visit(clientRoutes.index().url);
    });

    useNativeEvent('App\\Events\\Native\\OpenCreateProjectFromMenu', () => {
        projectSheetOpen.value = true;

        if (isCurrentUrl(projectRoutes.index().url)) {
            return;
        }

        router.visit(projectRoutes.index().url);
    });
}
