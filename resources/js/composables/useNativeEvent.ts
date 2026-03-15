import { onMounted, onUnmounted } from 'vue';

export function useNativeEvent<T>(eventClass: string, callback: (payload: T) => void): void {
    let mounted = false;
    let onNativeInit: (() => void) | null = null;

    onMounted(() => {
        mounted = true;

        const handler = (payload: unknown) => {
            if (mounted) {
                callback(payload as T);
            }
        };

        onNativeInit = () => window.Native?.on(eventClass, handler);
        window.addEventListener('native:init', onNativeInit);

        if (window.Native) {
            window.Native.on(eventClass, handler);
        }
    });

    onUnmounted(() => {
        mounted = false;

        if (onNativeInit) {
            window.removeEventListener('native:init', onNativeInit);
        }
    });
}
