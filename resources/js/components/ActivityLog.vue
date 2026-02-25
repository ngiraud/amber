<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { cn } from '@/lib/utils';
import type { ActivityEvent } from '@/types';

type ActivityLogProps = {
    events: { data: ActivityEvent[] };
    hasNewEvents: boolean;
    propName?: string;
    preserveUrl?: boolean;
    scrollClass?: string;
};

const props = withDefaults(defineProps<ActivityLogProps>(), {
    propName: 'events',
    preserveUrl: true,
    scrollClass: 'overflow-y-auto',
    hasNewEvents: false,
});

const sinceId = ref<string | null>(props.events.data[0]?.id ?? null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({
            only: ['hasNewEvents'],
            data: sinceId.value ? { since_id: sinceId.value } : {},
            preserveUrl: props.preserveUrl,
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
    }
});

function refresh(): void {
    router.reload({
        only: [props.propName, 'hasNewEvents'],
        reset: [props.propName],
        onSuccess: () => {
            sinceId.value = props.events.data[0]?.id ?? null;
        },
    });
}
</script>

<template>
    <div :class="cn('rounded-md bg-zinc-950 p-3 font-mono text-xs', scrollClass)">
        <Transition name="banner">
            <div v-if="hasNewEvents" class="mb-2 flex items-center justify-between rounded bg-zinc-800 px-3 py-1.5">
                <span class="text-zinc-300">New events available</span>
                <button class="text-xs text-zinc-400 hover:text-zinc-200" @click="refresh">Refresh ↺</button>
            </div>
        </Transition>

        <InfiniteScroll :data="propName" :preserve-url="preserveUrl">
            <template #loading>Loading activity events</template>

            <TransitionGroup name="log-entry" tag="div">
                <div v-for="event in events.data" :key="event.id" class="flex items-baseline gap-3 py-0.5 leading-relaxed">
                    <span class="shrink-0 text-zinc-500">[{{ event.occurred_at_formatted }}]</span>
                    <span :class="cn('w-20 shrink-0 truncate', event.source_type.color)">{{ event.source_type.label }}</span>
                    <span :class="cn('w-30 shrink-0 truncate', event.source_type.color)">{{ event.type.label }}</span>
                    <span class="min-w-0 truncate text-zinc-300">{{ event.detail }}</span>
                    <span class="ml-auto shrink-0 text-zinc-600">{{ event.project_name }} ({{ event.repository_name }})</span>
                </div>
            </TransitionGroup>

            <p v-if="events?.data?.length === 0" class="py-2 text-zinc-500">No activity recorded yet.</p>
        </InfiniteScroll>
    </div>
</template>

<style scoped>
.log-entry-enter-active {
    transition: opacity 0.3s ease;
}

.log-entry-enter-from {
    opacity: 0;
}

.banner-enter-active,
.banner-leave-active {
    transition: opacity 0.2s ease;
}

.banner-enter-from,
.banner-leave-to {
    opacity: 0;
}
</style>
