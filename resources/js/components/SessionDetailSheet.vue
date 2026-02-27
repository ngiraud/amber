<script setup lang="ts">
import { watch } from 'vue';
import ActivityLog from '@/components/ActivityLog.vue';
import SessionTimer from '@/components/SessionTimer.vue';
import { Badge } from '@/components/ui/badge';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useDateFormat } from '@/composables/useDateFormat';
import type { ActivityEvent, Paginator, Session } from '@/types';

const props = defineProps<{
    session: Session;
    events?: Paginator<ActivityEvent> | null;
    hasNewEvents?: boolean;
    eventsPropName?: string;
}>();

const emit = defineEmits<{
    open: [];
    close: [];
}>();

const open = defineModel<boolean>('open', { default: false });

const { formatDateTime } = useDateFormat();

function formatMinutes(minutes: number | null): string {
    if (!minutes) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

watch(open, (isOpen) => {
    if (isOpen) {
        emit('open');
    } else {
        emit('close');
    }
});
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="flex flex-col gap-0 overflow-hidden p-0 sm:max-w-[80%]">
            <SheetHeader class="shrink-0 border-b p-6 pb-4">
                <SheetTitle>Session Details</SheetTitle>
            </SheetHeader>

            <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                v-if="session.project?.color"
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: session.project.color }"
                            />
                            <p class="text-sm font-medium">{{ session.project?.client?.name }} — {{ session.project?.name }}</p>
                        </div>

                        <Badge v-if="session.ended_at === null" class="bg-green-500 text-white">Active</Badge>
                        <Badge v-else-if="session.is_validated" variant="secondary">Validated</Badge>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Started</p>
                            <p class="mt-0.5">{{ formatDateTime(session.started_at) }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-muted-foreground">Ended</p>
                            <p class="mt-0.5">{{ session.ended_at ? formatDateTime(session.ended_at) : '—' }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-muted-foreground">Duration</p>
                            <p class="mt-0.5 font-mono">
                                <SessionTimer v-if="session.ended_at === null" :started-at="session.started_at" />
                                <span v-else>{{ formatMinutes(session.rounded_minutes) }}</span>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted-foreground">Source</p>
                            <p class="mt-0.5">{{ session.source.label }}</p>
                        </div>
                    </div>

                    <div v-if="session.description" class="mt-4">
                        <p class="text-xs text-muted-foreground">Description</p>
                        <p class="mt-0.5 text-sm">{{ session.description }}</p>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col">
                    <h3 class="shrink-0 text-sm font-semibold">Activity Events</h3>

                    <div class="mt-2 min-h-0 flex-1 flex-col">
                        <div v-if="events === null || events === undefined" class="h-40 animate-pulse rounded-md bg-zinc-950" />
                        <ActivityLog
                            v-else
                            :events="events"
                            :has-new-events="hasNewEvents ?? false"
                            :prop-name="eventsPropName ?? 'events'"
                            scroll-class="max-h-full overflow-y-auto"
                        />
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
