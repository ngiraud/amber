<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useDateFormat } from '@/composables/useDateFormat';
import type { Session } from '@/types';

const props = defineProps<{
    sessions: Session[];
    date: string;
}>();

const { formatTime } = useDateFormat();
const now = ref(new Date());
let timer: any;

const page = usePage();
const globalActiveSession = computed(() => page.props.activeSession as Session | null);

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 10000); // Check every 10 seconds for better precision
});

onUnmounted(() => {
    clearInterval(timer);
});

const MINUTES_IN_DAY = 24 * 60;

function getMinutesSinceStartOfDay(dateString: string): number {
    const date = new Date(dateString);
    return date.getHours() * 60 + date.getMinutes();
}

const isToday = computed(() => {
    const today = new Date();
    const d = new Date(props.date + 'T00:00:00');
    return today.toDateString() === d.toDateString();
});

const nowPosition = computed(() => {
    const minutes = now.value.getHours() * 60 + now.value.getMinutes();
    return `${(minutes / MINUTES_IN_DAY) * 100}%`;
});

const nowLabel = computed(() => {
    return formatTime(now.value.toISOString());
});

const allSessions = computed(() => {
    const sessions = [...props.sessions];

    // Add active session if it's today and not already in the list
    if (isToday.value && globalActiveSession.value) {
        const alreadyExists = sessions.some((s) => s.id === globalActiveSession.value?.id);
        if (!alreadyExists) {
            sessions.push(globalActiveSession.value);
        }
    }

    return sessions;
});

const sessionsWithPositions = computed(() => {
    const sorted = [...allSessions.value].sort((a, b) => new Date(a.started_at).getTime() - new Date(b.started_at).getTime());

    const tracks: number[][] = [];

    return sorted.map((session) => {
        const start = getMinutesSinceStartOfDay(session.started_at);
        const end = session.ended_at ? getMinutesSinceStartOfDay(session.ended_at) : now.value.getHours() * 60 + now.value.getMinutes();

        let trackIndex = tracks.findIndex((trackEnd) => trackEnd <= start);
        if (trackIndex === -1) {
            trackIndex = tracks.length;
            tracks.push(end);
        } else {
            tracks[trackIndex] = end;
        }

        return {
            ...session,
            left: `${(start / MINUTES_IN_DAY) * 100}%`,
            width: `${Math.max(((end - start) / MINUTES_IN_DAY) * 100, 0.6)}%`,
            track: trackIndex,
            timeLabel: `${formatTime(session.started_at)} - ${session.ended_at ? formatTime(session.ended_at) : 'Now'}`,
        };
    });
});

const maxTrack = computed(() => Math.max(...sessionsWithPositions.value.map((s) => s.track), 0));
const hours = [0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24];
</script>

<template>
    <TooltipProvider>
        <div class="space-y-2">
            <div
                class="relative w-full overflow-visible rounded-lg border bg-muted/20 transition-all duration-300"
                :style="{ height: `${(maxTrack + 1) * 28 + 10}px` }"
            >
                <!-- Hour vertical lines -->
                <div
                    v-for="hour in hours"
                    :key="hour"
                    class="absolute z-0 h-full border-l border-border/30"
                    :style="{ left: `${(hour / 24) * 100}%` }"
                />

                <!-- Now Marker (Line + Time) -->
                <div v-if="isToday" class="absolute z-10 h-full w-0.5 bg-primary/40" :style="{ left: nowPosition }">
                    <span class="absolute bottom-full mb-1 -translate-x-1/2 text-[9px] font-black text-primary tabular-nums">
                        {{ nowLabel }}
                    </span>
                </div>

                <!-- Sessions -->
                <template v-for="session in sessionsWithPositions" :key="session.id">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div
                                class="absolute h-5 rounded-md border border-black/30 opacity-90 shadow-sm transition-all hover:z-20 hover:scale-y-110 hover:opacity-100"
                                :class="[!session.ended_at ? 'animate-pulse ring-1 ring-white/10' : '']"
                                :style="{
                                    left: session.left,
                                    width: session.width,
                                    top: `${session.track * 28 + 8}px`,
                                    backgroundColor: session.project?.color ?? '#94a3b8',
                                }"
                            />
                        </TooltipTrigger>
                        <TooltipContent side="top" class="flex flex-col gap-0.5 p-2">
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full" :style="{ backgroundColor: session.project?.color ?? '#94a3b8' }" />
                                <span class="text-xs font-bold">{{ session.project?.name ?? 'Unknown' }}</span>
                            </div>
                            <p class="text-[10px] font-bold text-muted-foreground">{{ session.timeLabel }}</p>
                            <p
                                v-if="session.description"
                                class="mt-1 max-w-[200px] border-t border-border/20 pt-1 text-[10px] leading-tight font-medium text-muted-foreground"
                            >
                                {{ session.description }}
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </template>
            </div>

            <!-- Hour Labels -->
            <div class="relative h-4 w-full">
                <div
                    v-for="hour in hours"
                    :key="hour"
                    class="absolute -translate-x-1/2 text-[10px] font-black tracking-tighter text-muted-foreground/60 uppercase tabular-nums"
                    :style="{ left: `${(hour / 24) * 100}%` }"
                >
                    <template v-if="hour === 0 || hour === 24"></template>
                    <template v-else>{{ String(hour).padStart(2, '0') }}:00</template>
                </div>
            </div>
        </div>
    </TooltipProvider>
</template>
