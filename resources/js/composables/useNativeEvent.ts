import { onMounted } from 'vue';

declare global {
    interface Window {
        Native?: {
            on: (event: string, callback: (payload: unknown) => void) => void;
        };
    }
}

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
