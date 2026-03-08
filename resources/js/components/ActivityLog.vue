<script setup lang="ts">
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { LucideRotateCcw } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useDateFormat } from '@/composables/useDateFormat';
import { cn } from '@/lib/utils';
import type { ActivityEvent, Paginator } from '@/types';

type ActivityLogProps = {
    propName?: string;
    preserveUrl?: boolean;
    scrollClass?: string;
};

const props = withDefaults(defineProps<ActivityLogProps>(), {
    propName: 'events',
    preserveUrl: true,
    scrollClass: 'overflow-y-auto',
});

const page = usePage<{ events?: Paginator<ActivityEvent>; hasNewEvents?: boolean }>();
const events = computed(() => page.props.events);
const hasNewEvents = computed(() => page.props.hasNewEvents ?? false);

const { formatDateTimeISO } = useDateFormat();

const scrollContainer = useTemplateRef<HTMLDivElement>('scrollContainer');
const sinceOccurredAt = ref<number | null>(page.props.events?.data[0]?.occurred_at_timestamp ?? null);

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
let scrollEndTimer: ReturnType<typeof setTimeout> | null = null;
const isScrolling = ref(false);

function onScroll(): void {
    isScrolling.value = true;
    if (scrollEndTimer !== null) {
        clearTimeout(scrollEndTimer);
    }
    scrollEndTimer = setTimeout(() => {
        isScrolling.value = false;
    }, 1000);
}

onMounted(() => {
    pollTimer = setInterval(() => {
        if (isScrolling.value) {
            return;
        }
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
    if (scrollEndTimer !== null) {
        clearTimeout(scrollEndTimer);
    }
});

function refresh(): void {
    router.reload({
        only: [props.propName, 'hasNewEvents'],
        reset: [props.propName],
        onSuccess: () => {
            sinceOccurredAt.value = events.value?.data[0]?.occurred_at_timestamp ?? null;
            scrollContainer.value?.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
}
</script>

<template>
    <div
        ref="scrollContainer"
        :class="cn('relative h-full overflow-y-auto rounded-md bg-olive-900 p-3 font-mono text-xs dark:bg-olive-900', scrollClass)"
        @scroll="onScroll"
    >
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

        <InfiniteScroll :data="propName" :preserve-url="preserveUrl" :buffer="300" only-next>
            <template #next="{ loading }">
                <template v-if="loading">
                    <div v-for="i in 3" :key="i" class="flex items-center gap-3 py-0.5 leading-relaxed">
                        <Skeleton class="h-3 w-36 shrink-0 bg-olive-700" />
                        <Skeleton class="h-3 w-20 shrink-0 bg-olive-700" />
                        <Skeleton class="h-3 w-30 shrink-0 bg-olive-700" />
                        <Skeleton class="h-3 min-w-0 grow bg-olive-700" />
                        <Skeleton class="ml-auto h-3 w-24 shrink-0 bg-olive-700" />
                    </div>
                </template>
            </template>

            <TransitionGroup name="log-entry">
                <div v-for="event in events?.data" :key="event.id" class="flex items-baseline gap-3 py-0.5 leading-relaxed">
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
            </TransitionGroup>

            <p v-if="events?.data?.length === 0" class="py-2 text-olive-500">No activity recorded yet.</p>
        </InfiniteScroll>
    </div>
</template>
