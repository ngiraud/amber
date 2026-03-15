import { ref } from 'vue';

const shouldOpen = ref(false);

export function useOpenProjectSheet() {
    return { shouldOpen };
}
