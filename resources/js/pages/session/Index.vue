<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import SessionTimer from '@/components/SessionTimer.vue';
import StartSessionDialog from '@/components/StartSessionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import type { Paginator, Project, Session } from '@/types';

defineProps<{
    sessions: Paginator<Session>;
    projects: Project[];
}>();

function formatDuration(minutes: number | null): string {
    if (minutes === null) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}
</script>

<template>
    <AppLayout title="Sessions">
        <template #header>
            <PageHeader title="Sessions">
                <template #actions>
                    <StartSessionDialog :projects="projects">
                        <Button size="sm">Start Session</Button>
                    </StartSessionDialog>
                </template>
            </PageHeader>
        </template>

        <div v-if="sessions.data.length === 0" class="mt-6 text-center">
            <p class="text-sm text-muted-foreground">No sessions yet.</p>
        </div>

        <div v-else class="flex flex-col gap-1.5">
            <Link
                v-for="session in sessions.data"
                :key="session.id"
                :href="sessionRoutes.show(session)"
                class="flex items-center justify-between rounded-lg border bg-card px-5 py-4 text-card-foreground transition-colors hover:bg-accent"
            >
                <div>
                    <p class="text-sm font-medium">{{ session.project?.client?.name }} — {{ session.project?.name }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ new Date(session.started_at).toLocaleString() }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Badge v-if="session.source.label === 'Manual'" variant="secondary">Manual</Badge>

                    <span class="font-mono text-sm text-muted-foreground">
                        <SessionTimer v-if="session.ended_at === null" :started-at="session.started_at" />
                        <span v-else>{{ formatDuration(session.duration_minutes) }}</span>
                    </span>

                    <Badge v-if="session.ended_at === null" class="bg-green-500 text-white">Active</Badge>
                </div>
            </Link>

            <div v-if="sessions.last_page > 1" class="mt-4 flex items-center justify-between">
                <Button v-if="sessions.prev_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="sessions.prev_page_url">← Previous</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">← Previous</span>

                <span class="text-xs text-muted-foreground">Page {{ sessions.current_page }} of {{ sessions.last_page }}</span>

                <Button v-if="sessions.next_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="sessions.next_page_url">Next →</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
