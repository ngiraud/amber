<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { LucideRotateCcw } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useDateFormat } from '@/composables/useDateFormat';
import { cn } from '@/lib/utils';
import type { ActivityEvent, Paginator } from '@/types';

type ActivityLogProps = {
    events?: Paginator<ActivityEvent>;
    hasNewEvents?: boolean;
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

const { formatDateTimeISO } = useDateFormat();

const scrollContainer = useTemplateRef<HTMLDivElement>('scrollContainer');
const sinceOccurredAt = ref<number | null>(props.events?.data[0]?.occurred_at_timestamp ?? null);

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
    <div ref="scrollContainer" :class="cn('relative rounded-md bg-olive-900 p-3 font-mono text-xs dark:bg-olive-900', scrollClass)">
        <Transition name="banner">
            <div v-if="hasNewEvents" class="sticky top-0 mb-2 flex items-center justify-between rounded bg-olive-800 px-3 py-1.5 dark:bg-olive-800">
                <span class="text-olive-300">New events available</span>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-auto gap-1.5 border-olive-600 bg-transparent px-2.5 py-1 text-xs text-olive-300 hover:border-olive-500 hover:bg-olive-700 hover:text-olive-100 dark:border-olive-500 dark:bg-transparent dark:hover:border-olive-400 dark:hover:bg-olive-600"
                    @click="refresh"
                >
                    <span>Refresh</span>
                    <span><LucideRotateCcw class="size-2.5" /></span>
                </Button>
            </div>
        </Transition>

        <InfiniteScroll :data="propName" :preserve-url="preserveUrl" :buffer="800">
            <template #loading>Loading activity events</template>

            <div v-for="event in events.data" :key="event.id" class="flex items-baseline gap-3 py-0.5 leading-relaxed">
                <span class="shrink-0 text-olive-500">[{{ formatDateTimeISO(event.occurred_at) }}]</span>
                <span :class="cn('w-20 shrink-0 truncate', event.source_type.color)">{{ event.source_type.label }}</span>
                <span :class="cn('w-30 shrink-0 truncate', event.source_type.color)">{{ event.type.label }}</span>
                <Tooltip :open="openTooltipId === event.id">
                    <TooltipTrigger as-child>
                        <span
                            class="min-w-0 truncate text-olive-300"
                            @mouseenter="onDetailMouseEnter($event, event.id)"
                            @mouseleave="onDetailMouseLeave"
                        >
                            {{ event.detail }}
                        </span>
                    </TooltipTrigger>
                    <TooltipContent align="start">{{ event.detail }}</TooltipContent>
                </Tooltip>
                <span class="ml-auto shrink-0 text-olive-500">{{ event.project_name }} ({{ event.repository_name }})</span>
            </div>

            <p v-if="events?.data?.length === 0" class="py-2 text-olive-500">No activity recorded yet.</p>
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
