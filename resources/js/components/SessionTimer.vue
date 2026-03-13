<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    startedAt: string;
}>();

function computeElapsed(): string {
    const start = new Date(props.startedAt).getTime();
    const totalSeconds = Math.floor((Date.now() - start) / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

const elapsed = ref(computeElapsed());
let interval: ReturnType<typeof setInterval>;

onMounted(() => {
    interval = setInterval(() => {
        elapsed.value = computeElapsed();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <span>{{ elapsed }}</span>
</template>
