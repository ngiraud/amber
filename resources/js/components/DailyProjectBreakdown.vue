<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProjectBreakdownItem from '@/components/ProjectBreakdownItem.vue';
import { useNow } from '@/composables/useNow';
import type { Session } from '@/types';

const props = defineProps<{
    sessions: Session[];
    date?: string;
}>();

const { now, isToday: isTodayFn } = useNow();

const page = usePage();
const globalActiveSession = computed(() => page.props.activeSession as Session | null);

const isToday = computed(() => !props.date || isTodayFn(props.date));

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

const totalMinutes = computed(() => projectSummary.value.reduce((sum, p) => sum + p.minutes, 0) || 1);

const projectsWithPercentage = computed(() =>
    projectSummary.value.map((p) => ({
        ...p,
        percentage: Math.round((p.minutes / totalMinutes.value) * 100),
    })),
);
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <ProjectBreakdownItem
            v-for="project in projectsWithPercentage"
            :key="project.name"
            :name="project.name"
            :color="project.color"
            :minutes="project.minutes"
            :percentage="project.percentage"
        />
    </div>
</template>
