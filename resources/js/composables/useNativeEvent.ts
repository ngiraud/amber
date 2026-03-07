import { onMounted } from 'vue';

export function useNativeEvent<T>(eventClass: string, callback: (payload: T) => void): void {
    onMounted(() => {
        window.addEventListener('native:init', () => {
            window.Native?.on(eventClass, (payload) => callback(payload as T));
        });

        if (window.Native) {
            window.Native.on(eventClass, (payload) => callback(payload as T));
        }
    });
}
