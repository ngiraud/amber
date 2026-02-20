<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

type FlashEntry = { type: 'success' | 'error'; message: string };

const flash = ref<FlashEntry | null>(null);
let timer: ReturnType<typeof setTimeout>;

function show(type: FlashEntry['type'], message: string): void {
    flash.value = { type, message };
    clearTimeout(timer);
    timer = setTimeout(() => {
        flash.value = null;
    }, 3500);
}

const off = router.on('flash', (event) => {
    const { success, error } = event.detail.flash;
    if (success) show('success', success);
    else if (error) show('error', error);
});

onUnmounted(() => {
    off();
    clearTimeout(timer);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="flash"
            class="fixed right-5 bottom-5 z-50 rounded-lg px-4 py-3 text-sm font-medium shadow-lg"
            :class="{
                'bg-green-600 text-white': flash.type === 'success',
                'bg-red-600 text-white': flash.type === 'error',
            }"
        >
            {{ flash.message }}
        </div>
    </Transition>
</template>
