<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useDateFormat } from '@/composables/useDateFormat';
import { t } from '@/composables/useTranslation';
import { formatMinutes } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
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

function getMinutesSinceStartOfDay(dateString: string): number {
    const date = new Date(dateString);

    return date.getHours() * 60 + date.getMinutes();
}

const isToday = computed(() => {
    const today = new Date();
    const d = new Date(props.date + 'T00:00:00');

    return today.toDateString() === d.toDateString();
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

// Adaptive Zoom Logic
const visibleRange = computed(() => {
    if (allSessions.value.length === 0) {
        return { start: 8, end: 19 }; // Default business hours
    }

    let minMinutes = 24 * 60;
    let maxMinutes = 0;

    allSessions.value.forEach((s) => {
        const start = getMinutesSinceStartOfDay(s.started_at);
        const end = s.ended_at ? getMinutesSinceStartOfDay(s.ended_at) : now.value.getHours() * 60 + now.value.getMinutes();
        minMinutes = Math.min(minMinutes, start);
        maxMinutes = Math.max(maxMinutes, end);
    });

    // Padding of 1 hour before and after
    let startHour = Math.max(0, Math.floor(minMinutes / 60) - 1);
    let endHour = Math.min(24, Math.ceil(maxMinutes / 60) + 1);

    // Ensure we see at least 8 hours for stability
    if (endHour - startHour < 8) {
        const center = (startHour + endHour) / 2;
        startHour = Math.max(0, Math.floor(center - 4));
        endHour = Math.min(24, startHour + 8);

        if (endHour === 24) {
            startHour = 16;
        }
    }

    return { start: startHour, end: endHour };
});

const totalMinutesVisible = computed(() => (visibleRange.value.end - visibleRange.value.start) * 60);

const hours = computed(() => {
    const list = [];

    for (let h = visibleRange.value.start; h <= visibleRange.value.end; h++) {
        list.push(h);
    }

    return list;
});

const nowPosition = computed(() => {
    const minutes = now.value.getHours() * 60 + now.value.getMinutes();
    const relativeMinutes = minutes - visibleRange.value.start * 60;

    return `${(relativeMinutes / totalMinutesVisible.value) * 100}%`;
});

const nowLabel = computed(() => {
    return formatTime(now.value.toISOString());
});

const projectsWithSessions = computed(() => {
    const projectMap = new Map<string, { project: any; sessions: Session[] }>();

    allSessions.value.forEach((s) => {
        const pId = s.project?.id || 'unknown';

        if (!projectMap.has(pId)) {
            projectMap.set(pId, { project: s.project, sessions: [] });
        }

        projectMap.get(pId)!.sessions.push(s);
    });

    // Sort projects by their first session's start time
    return Array.from(projectMap.values()).sort((a, b) => {
        const aStart = Math.min(...a.sessions.map((s) => new Date(s.started_at).getTime()));
        const bStart = Math.min(...b.sessions.map((s) => new Date(s.started_at).getTime()));

        return aStart - bStart;
    });
});

const sessionsWithPositions = computed(() => {
    let currentTrackOffset = 0;
    const result: any[] = [];

    projectsWithSessions.value.forEach((pGroup) => {
        const projectTracks: number[] = [];

        pGroup.sessions.sort((a, b) => new Date(a.started_at).getTime() - new Date(b.started_at).getTime());

        pGroup.sessions.forEach((session) => {
            const start = getMinutesSinceStartOfDay(session.started_at);
            const end = session.ended_at ? getMinutesSinceStartOfDay(session.ended_at) : now.value.getHours() * 60 + now.value.getMinutes();

            const duration = end - start;

            let trackIndexInProject = projectTracks.findIndex((tEnd) => tEnd <= start);

            if (trackIndexInProject === -1) {
                trackIndexInProject = projectTracks.length;
                projectTracks.push(end);
            } else {
                projectTracks[trackIndexInProject] = end;
            }

            const relativeStart = start - visibleRange.value.start * 60;
            const left = (relativeStart / totalMinutesVisible.value) * 100;
            const width = (duration / totalMinutesVisible.value) * 100;

            result.push({
                ...session,
                left: `${left}%`,
                width: `${Math.max(width, 0.4)}%`,
                isLargeEnoughForLabel: width > 3,
                track: currentTrackOffset + trackIndexInProject,
                duration,
                timeLabel: `${formatTime(session.started_at)} - ${session.ended_at ? formatTime(session.ended_at) : 'Now'}`,
            });
        });

        currentTrackOffset += projectTracks.length;
    });

    return result;
});

const sessionsByTrack = computed(() => {
    const tracks: (typeof sessionsWithPositions.value)[] = [];

    for (const session of sessionsWithPositions.value) {
        while (tracks.length <= session.track) {
            tracks.push([]);
        }

        tracks[session.track].push(session);
    }

    return tracks.length > 0 ? tracks : [[]];
});

function getContrastColor(hex: string): string {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.55 ? '#1a1a1a' : '#ffffff';
}

function navigateToSession(session: Session) {
    if (session.id) {
        router.visit(sessionRoutes.show(session).url);
    }
}
</script>

<template>
    <TooltipProvider>
        <div class="space-y-4">
            <div class="relative w-full rounded-xl border bg-muted/10 transition-all duration-500 ease-in-out">
                <!-- Hour vertical lines & Grid -->
                <div
                    v-for="hour in hours"
                    :key="hour"
                    class="absolute z-0 h-full border-l border-border/20"
                    :style="{ left: `${((hour - visibleRange.start) / (visibleRange.end - visibleRange.start)) * 100}%` }"
                />

                <!-- Now Marker -->
                <div
                    v-if="isToday && now.getHours() >= visibleRange.start && now.getHours() <= visibleRange.end"
                    class="absolute z-10 h-full w-px bg-primary/40"
                    :style="{ left: nowPosition }"
                >
                    <div class="absolute top-1.5 -left-1 size-2 rounded-full bg-primary shadow-[0_0_8px_rgba(var(--primary),0.5)]" />
                    <span
                        class="absolute bottom-full mb-0.5 -translate-x-1/2 rounded bg-primary px-1 text-[8px] font-black text-primary-foreground tabular-nums shadow-sm"
                    >
                        {{ nowLabel }}
                    </span>
                </div>

                <!-- Track rows -->
                <div class="flex flex-col gap-2 py-2">
                    <div v-for="(trackSessions, trackIndex) in sessionsByTrack" :key="trackIndex" class="relative h-7">
                        <template v-for="session in trackSessions" :key="session.id">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div
                                        class="absolute inset-y-0 flex cursor-pointer items-center overflow-hidden rounded-lg border border-white/10 p-1 shadow-sm transition-all hover:z-20 hover:scale-[1.02] hover:shadow-md hover:brightness-110 active:scale-[0.98]"
                                        :class="[!session.ended_at ? 'active-session-stripes ring-2 ring-primary/20' : '']"
                                        :style="{
                                            left: session.left,
                                            width: session.width,
                                            backgroundColor: session.project?.color ?? '#94a3b8',
                                        }"
                                        @click="navigateToSession(session as any)"
                                    >
                                        <div
                                            v-if="session.isLargeEnoughForLabel"
                                            class="flex w-full items-center gap-2 overflow-hidden px-1"
                                            :style="{ color: getContrastColor(session.project?.color ?? '#94a3b8') }"
                                        >
                                            <span class="truncate text-[10px] font-black tracking-tight opacity-90">
                                                {{ session.project?.name }}
                                            </span>
                                            <span class="ml-auto shrink-0 font-mono text-[9px] font-bold opacity-60">
                                                {{ formatMinutes(session.duration) }}
                                            </span>
                                        </div>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent side="top" class="flex flex-col gap-0.5 p-3 shadow-xl">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 rounded-full border border-white/20"
                                            :style="{ backgroundColor: session.project?.color ?? '#94a3b8' }"
                                        />
                                        <span class="text-xs font-black">{{ session.project?.name ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] font-bold text-muted-foreground">
                                        <span>{{ session.timeLabel }}</span>
                                        <span class="opacity-30">|</span>
                                        <span class="font-mono">{{ formatMinutes(session.duration) }}</span>
                                    </div>
                                    <p
                                        v-if="session.description"
                                        class="mt-2 max-w-[240px] border-t border-border/20 pt-2 text-[10px] leading-relaxed font-medium text-muted-foreground"
                                    >
                                        {{ session.description }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Hour labels -->
            <div class="relative h-4 w-full">
                <div
                    v-for="hour in hours"
                    :key="hour"
                    class="absolute -translate-x-1/2 text-[10px] font-black tracking-tighter text-muted-foreground/60 uppercase tabular-nums"
                    :style="{ left: `${((hour - visibleRange.start) / (visibleRange.end - visibleRange.start)) * 100}%` }"
                >
                    <template v-if="hour !== visibleRange.start && hour !== visibleRange.end">{{ String(hour).padStart(2, '0') }}h</template>
                </div>
            </div>

            <!-- Legend -->
            <div v-if="globalActiveSession" class="flex items-center gap-1.5 px-1">
                <div class="active-session-stripes size-2 rounded-full bg-primary" />
                <span class="text-[9px] font-black tracking-widest text-muted-foreground uppercase">{{ t('app.common.live') }}</span>
            </div>
        </div>
    </TooltipProvider>
</template>

<style scoped>
.active-session-stripes {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 20px 20px;
    animation: move-stripes 1s linear infinite;
}

@keyframes move-stripes {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 20px 0;
    }
}
</style>
