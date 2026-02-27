<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from 'lucide-vue-next';
import ActivityLog from '@/components/ActivityLog.vue';
import PageHeader from '@/components/PageHeader.vue';
import SessionTimer from '@/components/SessionTimer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useDateFormat } from '@/composables/useDateFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ActivityEvent, Paginator, Session } from '@/types';

const props = defineProps<{
    session: Session;
    backUrl: string;
    events: Paginator<ActivityEvent>;
    hasNewEvents?: boolean;
}>();

const { formatDateTime } = useDateFormat();

function formatMinutes(minutes: number | null): string {
    if (!minutes) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}
</script>

<template>
    <AppLayout title="Session">
        <template #header>
            <PageHeader :title="session.project?.client?.name + ' — ' + session.project?.name">
                <template #actions>
                    <Button variant="outline" size="sm" @click="router.visit(props.backUrl)">
                        <ArrowLeftIcon class="mr-1.5 size-3.5" />
                        Back
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="flex flex-col gap-6">
            <Card>
                <CardContent>
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
                </CardContent>
            </Card>

            <div class="mt-8 flex min-h-0 flex-1 flex-col">
                <h2 class="shrink-0 text-base font-semibold">Activity Events</h2>

                <div class="mt-3 min-h-0 flex-1">
                    <ActivityLog :events="events" :has-new-events="hasNewEvents ?? false" scroll-class="h-full overflow-y-auto" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
