<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import SessionTimer from '@/components/SessionTimer.vue';
import { Badge } from '@/components/ui/badge';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as sessionRoutes from '@/routes/sessions';
import type { Session } from '@/types';

const props = defineProps<{
    session: Session;
}>();

function formatDuration(minutes: number | null): string {
    if (minutes === null) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

const isActive = props.session.ended_at === null;
</script>

<template>
    <AppLayout :title="`Session — ${session.project?.name ?? ''}`">
        <div class="flex items-center justify-between">
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link :href="sessionRoutes.index()">Sessions</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Session</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <Form v-if="isActive" :action="sessionRoutes.stop(session)" method="patch" #default="{ submit, processing }">
                <Button variant="outline" size="sm" :disabled="processing" @click="submit">
                    {{ processing ? 'Stopping…' : 'Stop Session' }}
                </Button>
            </Form>
        </div>

        <div class="mt-6 max-w-lg space-y-6">
            <div class="rounded-lg border bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Project</p>
                        <p class="mt-1 text-sm font-medium">{{ session.project?.client?.name }} — {{ session.project?.name }}</p>
                    </div>

                    <Badge v-if="isActive" class="bg-green-500 text-white">Active</Badge>
                    <Badge v-else-if="session.is_validated" variant="secondary">Validated</Badge>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-muted-foreground">Started</p>
                        <p class="mt-1 text-sm">{{ new Date(session.started_at).toLocaleString() }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground">Ended</p>
                        <p class="mt-1 text-sm">
                            {{ session.ended_at ? new Date(session.ended_at).toLocaleString() : '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground">Duration</p>
                        <p class="mt-1 font-mono text-sm">
                            <SessionTimer v-if="isActive" :started-at="session.started_at" />
                            <span v-else>{{ formatDuration(session.duration_minutes) }}</span>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground">Source</p>
                        <p class="mt-1 text-sm">{{ session.source.label }}</p>
                    </div>
                </div>

                <div v-if="session.notes" class="mt-4">
                    <p class="text-xs text-muted-foreground">Notes</p>
                    <p class="mt-1 text-sm">{{ session.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
