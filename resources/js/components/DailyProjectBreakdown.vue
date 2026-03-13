<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Item, ItemContent, ItemTitle } from '@/components/ui/item';
import type { Session } from '@/types';

const props = defineProps<{
    sessions: Session[];
    date?: string;
}>();

const now = ref(new Date());
let timer: any;

const page = usePage();
const globalActiveSession = computed(() => page.props.activeSession as Session | null);

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 30000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const isToday = computed(() => {
    if (!props.date) {
        return true;
    }

    const d = new Date(props.date + 'T00:00:00');

    return now.value.toDateString() === d.toDateString();
});

const allSessions = computed(() => {
    const sessions = [...props.sessions];

    if (isToday.value && globalActiveSession.value) {
        const alreadyExists = sessions.some((s) => s.id === globalActiveSession.value?.id);

        if (!alreadyExists) {
            sessions.push(globalActiveSession.value);
        }
    }

    return sessions;
});

const projectSummary = computed(() => {
    const summary: Record<string, { name: string; color: string; minutes: number }> = {};

    allSessions.value.forEach((session) => {
        const projectId = session.project_id;
        let minutes = 0;

        if (session.ended_at) {
            // Finished session: use server rounded minutes
            minutes = session.rounded_minutes || 0;
        } else {
            // Active session: calculate purely in frontend to allow live ticking
            const start = new Date(session.started_at);
            const diff = now.value.getTime() - start.getTime();
            minutes = Math.max(Math.floor(diff / 1000 / 60), 0);
        }

        if (!summary[projectId]) {
            summary[projectId] = {
                name: session.project?.name ?? 'Unknown project',
                color: session.project?.color ?? '#94a3b8',
                minutes: 0,
            };
        }

        summary[projectId].minutes += minutes;
    });

    return Object.values(summary).sort((a, b) => b.minutes - a.minutes);
});

function formatMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;

    if (h === 0) {
        return `${m}m`;
    }

    if (m === 0) {
        return `${h}h`;
    }

    return `${h}h${String(m).padStart(2, '0')}m`;
}

const totalMinutes = computed(() => projectSummary.value.reduce((sum, p) => sum + p.minutes, 0) || 1);
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <Item v-for="project in projectSummary" :key="project.name" variant="muted" size="sm" class="border-border/40">
            <ItemContent class="flex-1">
                <div class="flex items-center justify-between gap-2">
                    <ItemTitle class="truncate text-[10px] font-black tracking-widest uppercase">
                        {{ project.name }}
                    </ItemTitle>
                    <span class="font-mono text-xs font-bold tabular-nums">
                        {{ formatMinutes(project.minutes) }}
                    </span>
                </div>

                <div class="relative mt-1.5 h-1 w-full overflow-hidden rounded-full bg-muted/50">
                    <div
                        class="h-full rounded-full transition-all duration-700 ease-out"
                        :style="{
                            backgroundColor: project.color,
                            width: `${(project.minutes / totalMinutes) * 100}%`,
                        }"
                    />
                </div>
            </ItemContent>
        </Item>
    </div>
</template>
