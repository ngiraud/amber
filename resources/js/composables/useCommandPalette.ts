import { ref } from 'vue';

const isOpen = ref(false);

export function useCommandPalette() {
    return { isOpen };
}
