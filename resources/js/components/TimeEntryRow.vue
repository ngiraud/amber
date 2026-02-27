<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDateFormat } from '@/composables/useDateFormat';
import * as sessionRoutes from '@/routes/sessions';
import type { Session } from '@/types';

const props = defineProps<{
    session: Session;
}>();

const { formatTime } = useDateFormat();

const confirmDelete = ref(false);

function formatMinutes(minutes: number | null): string {
    if (!minutes) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

function deleteSession(): void {
    router.delete(sessionRoutes.destroy(props.session).url, {
        preserveScroll: true,
    });
    confirmDelete.value = false;
}
</script>

<template>
    <div class="flex items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <span v-if="session.project?.color" class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: session.project.color }" />

            <div class="min-w-0">
                <p class="truncate text-sm font-medium">
                    {{ session.project?.name ?? 'Unknown project' }}
                </p>
                <p v-if="session.description" class="truncate text-xs text-muted-foreground">
                    {{ session.description }}
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <span class="font-mono text-sm text-muted-foreground"> {{ formatTime(session.started_at) }} → {{ formatTime(session.ended_at) }} </span>

            <span class="font-mono text-sm font-medium tabular-nums">
                {{ formatMinutes(session.rounded_minutes) }}
            </span>

            <Badge variant="secondary" class="text-xs">
                {{ session.source.label }}
            </Badge>

            <Button variant="ghost" size="icon" class="size-7 text-muted-foreground hover:text-destructive" @click="confirmDelete = true">
                <Trash2Icon class="size-3.5" />
            </Button>
        </div>
    </div>

    <ConfirmDialog
        :open="confirmDelete"
        title="Delete session?"
        message="This will permanently remove this session."
        confirm-label="Delete"
        @confirm="deleteSession"
        @cancel="confirmDelete = false"
    />
</template>
