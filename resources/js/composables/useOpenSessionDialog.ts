import { ref } from 'vue';

const shouldOpen = ref(false);

export function useOpenSessionDialog() {
    return { shouldOpen };
}
