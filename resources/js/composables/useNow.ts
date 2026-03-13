import { onMounted, onUnmounted, ref } from 'vue';

export function useNow(intervalMs = 30000) {
    const now = ref(new Date());
    let timer: ReturnType<typeof setInterval>;

    onMounted(() => {
        timer = setInterval(() => {
            now.value = new Date();
        }, intervalMs);
    });

    onUnmounted(() => {
        clearInterval(timer);
    });

    function isToday(date: string): boolean {
        return now.value.toDateString() === new Date(date + 'T00:00:00').toDateString();
    }

    return { now, isToday };
}
