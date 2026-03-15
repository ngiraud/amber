import { ref } from 'vue';

const shouldOpen = ref(false);

export function useOpenClientSheet() {
    return { shouldOpen };
}
