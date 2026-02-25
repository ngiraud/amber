<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { ActivityEvent, Paginator } from '@/types';

type ActivityLogProps = {
    events: Paginator<ActivityEvent>;
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

const scrollContainer = useTemplateRef<HTMLDivElement>('scrollContainer');
const sinceOccurredAt = ref<number | null>(props.events.data[0]?.occurred_at_timestamp ?? null);

const openTooltipId = ref<string | null>(null);

function onDetailMouseEnter(e: MouseEvent, id: string): void {
    const el = e.currentTarget as HTMLElement;
    if (el.scrollWidth > el.clientWidth) {
        openTooltipId.value = id;
    }
}

function onDetailMouseLeave(): void {
    openTooltipId.value = null;
}
let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({
            only: ['hasNewEvents'],
            data: sinceOccurredAt.value ? { since_occurred_at: sinceOccurredAt.value } : {},
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
            sinceOccurredAt.value = props.events.data[0]?.occurred_at_timestamp ?? null;
            scrollContainer.value?.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
}
</script>

<template>
    <div ref="scrollContainer" :class="cn('relative rounded-md bg-zinc-950 p-3 font-mono text-xs', scrollClass)">
        <Transition name="banner">
            <div v-if="hasNewEvents" class="sticky top-0 mb-2 flex items-center justify-between rounded bg-zinc-800 px-3 py-1.5">
                <span class="text-zinc-300">New events available</span>
                <Button size="sm" class="text-zinc-400 hover:text-zinc-200" @click="refresh">Refresh ↺</Button>
            </div>
        </Transition>

        <InfiniteScroll :data="propName" :preserve-url="preserveUrl" :buffer="200">
            <template #loading>Loading activity events</template>

            <TransitionGroup name="log-entry" tag="div">
                <div v-for="event in events.data" :key="event.id" class="flex items-baseline gap-3 py-0.5 leading-relaxed">
                    <span class="shrink-0 text-zinc-500">[{{ event.occurred_at_formatted }}]</span>
                    <span :class="cn('w-20 shrink-0 truncate', event.source_type.color)">{{ event.source_type.label }}</span>
                    <span :class="cn('w-30 shrink-0 truncate', event.source_type.color)">{{ event.type.label }}</span>
                    <Tooltip :open="openTooltipId === event.id">
                        <TooltipTrigger as-child>
                            <span
                                class="min-w-0 truncate text-zinc-300"
                                @mouseenter="onDetailMouseEnter($event, event.id)"
                                @mouseleave="onDetailMouseLeave"
                            >
                                {{ event.detail }}
                            </span>
                        </TooltipTrigger>
                        <TooltipContent align="start">{{ event.detail }}</TooltipContent>
                    </Tooltip>
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
