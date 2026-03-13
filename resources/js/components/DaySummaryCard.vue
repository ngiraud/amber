<script setup lang="ts">
import DailyProjectBreakdown from '@/components/DailyProjectBreakdown.vue';
import DayTimeline from '@/components/DayTimeline.vue';
import { Separator } from '@/components/ui/separator';
import type { Session } from '@/types';

defineProps<{
    sessions: Session[];
    date: string;
}>();
</script>

<template>
    <div class="space-y-10 rounded-2xl border bg-card p-6 shadow-sm ring-1 ring-border/5 ring-inset">
        <div class="space-y-6">
            <div class="flex items-center gap-3 px-1">
                <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">Visual Timeline</h3>
                <Separator class="flex-1 opacity-20" />
            </div>
            <DayTimeline :sessions="sessions" :date="date" />
        </div>

        <div class="space-y-6">
            <div class="flex items-center gap-3 px-1">
                <h3 class="text-[10px] font-black tracking-[0.25em] text-muted-foreground/80 uppercase">Project Allocation</h3>
                <Separator class="flex-1 opacity-20" />
            </div>
            <DailyProjectBreakdown :sessions="sessions" :date="date" />
        </div>
    </div>
</template>
