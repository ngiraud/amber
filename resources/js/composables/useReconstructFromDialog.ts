import { ref } from 'vue';
import type ReconstructDialog from '@/components/ReconstructDialog.vue';

const dialogRef = ref<InstanceType<typeof ReconstructDialog> | null>(null);

export function useReconstructFromDialog() {
    function show(date: string): void {
        dialogRef.value?.show(date);
    }

    return { dialogRef, show };
}
