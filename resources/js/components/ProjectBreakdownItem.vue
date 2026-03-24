<script setup lang="ts">
import { Item, ItemContent, ItemTitle } from '@/components/ui/item';
import { formatMinutes } from '@/lib/utils';

defineProps<{
    name: string | null;
    color: string | null;
    minutes: number;
    percentage: number;
}>();
</script>

<template>
    <Item variant="muted" size="sm" class="border-border/40">
        <ItemContent class="flex-1">
            <div class="flex items-center justify-between gap-2">
                <ItemTitle class="truncate text-[10px] font-black tracking-widest uppercase">
                    {{ name }}
                </ItemTitle>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-muted-foreground/60">{{ percentage }}%</span>
                    <span class="font-mono text-xs font-bold tabular-nums">{{ formatMinutes(minutes) }}</span>
                </div>
            </div>

            <div class="relative mt-1.5 h-1 w-full overflow-hidden rounded-full bg-muted/50">
                <div
                    class="h-full rounded-full transition-all duration-700 ease-out"
                    :style="{ backgroundColor: color ?? '#94a3b8', width: `${percentage}%` }"
                />
            </div>
        </ItemContent>
    </Item>
</template>
