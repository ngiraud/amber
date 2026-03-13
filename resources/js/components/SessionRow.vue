<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useDateFormat } from '@/composables/useDateFormat';
import { cn } from '@/lib/utils';
import * as sessionRoutes from '@/routes/sessions';
import type { Session } from '@/types';

const props = withDefaults(
    defineProps<{
        session: Session;
        showDate?: boolean;
        shouldDelete?: boolean;
    }>(),
    {
        showDate: false,
        shouldDelete: true,
    },
);

defineOptions({ inheritAttrs: false });

const { formatTime, formatDate } = useDateFormat();

const confirmDelete = ref(false);

const isActive = computed(() => !props.session.ended_at);

function formatMinutes(minutes: number | null): string {
    if (!minutes && !isActive.value) return '—';
    if (!minutes && isActive.value) return 'Running...';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}m`;
}

function deleteSession(): void {
    router.delete(sessionRoutes.destroy(props.session), {
        preserveScroll: true,
    });
    confirmDelete.value = false;
}
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <div
            v-bind="$attrs"
            :class="
                cn(
                    'group grid grid-cols-[1fr_auto] items-center gap-6 rounded-xl border bg-card px-6 py-3 shadow-sm ring-1 ring-border/5 transition-all ring-inset hover:bg-accent/40',
                    isActive && 'border-primary/20 bg-primary/30 ring-1 ring-primary/30',
                )
            "
        >
            <div class="flex min-w-0 items-center gap-4 py-1">
                <div class="relative flex size-2.5 items-center justify-center">
                    <span
                        v-if="isActive"
                        class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                        :style="{ backgroundColor: session.project?.color ?? '#94a3b8' }"
                    />
                    <span
                        v-if="session.project?.color"
                        class="relative inline-flex size-2.5 rounded-full border border-black/10"
                        :style="{ backgroundColor: session.project.color }"
                    />
                </div>

                <div class="min-w-0">
                    <p class="truncate text-[13px] font-bold tracking-tight">
                        {{ session.project?.name ?? 'Unknown project' }}
                    </p>
                    <p v-if="showDate" class="truncate text-[10px] font-bold tracking-widest text-muted-foreground uppercase opacity-80">
                        {{ formatDate(session.started_at) }}
                    </p>
                    <Tooltip v-else-if="session.description">
                        <TooltipTrigger as-child>
                            <p
                                class="cursor-help truncate text-[11px] leading-tight font-medium text-muted-foreground group-hover:text-foreground/80"
                            >
                                {{ session.description }}
                            </p>
                        </TooltipTrigger>
                        <TooltipContent side="bottom" align="start" class="max-w-[300px] border-border shadow-xl">
                            <p class="text-[11px] leading-relaxed font-medium">{{ session.description }}</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            </div>

            <!-- Technical Columns - All Right Aligned -->
            <div class="grid shrink-0 grid-cols-[120px_70px_80px_32px] items-center gap-4">
                <!-- Time Range -->
                <span class="text-right font-mono text-[10px] font-bold tracking-tighter text-muted-foreground">
                    {{ formatTime(session.started_at) }} <span class="opacity-30">→</span> {{ isActive ? 'Now' : formatTime(session.ended_at) }}
                </span>

                <!-- Duration -->
                <span :class="cn('text-right font-mono text-xs font-black text-foreground tabular-nums')">
                    {{ formatMinutes(session.rounded_minutes) }}
                </span>

                <!-- Source Badge -->
                <div class="flex justify-end">
                    <Badge
                        variant="outline"
                        class="pointer-events-none h-4.5 border-border/50 bg-background/50 px-2 text-[9px] font-black tracking-[0.15em] uppercase opacity-60 group-hover:opacity-100"
                    >
                        {{ session.source.label }}
                    </Badge>
                </div>

                <!-- Delete Action -->
                <div v-if="shouldDelete && !isActive" class="flex justify-end">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-6 text-muted-foreground/50 transition-colors group-hover:text-muted-foreground"
                        @click.stop="confirmDelete = true"
                    >
                        <Trash2Icon class="size-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </TooltipProvider>

    <ConfirmDialog
        :open="confirmDelete"
        title="Delete session?"
        message="This will permanently remove this session."
        confirm-label="Delete"
        @confirm="deleteSession"
        @cancel="confirmDelete = false"
    />
</template>
