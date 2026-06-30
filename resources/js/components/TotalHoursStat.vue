<script setup lang="ts">
import { ClockIcon } from 'lucide-vue-next';
import { StatItem, StatItemIcon, StatItemLabel, StatItemValue } from '@/components/stat';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { t } from '@/composables/useTranslation';
import { formatDays, formatMinutes } from '@/lib/utils';

const props = defineProps<{
    totalMinutes: number;
    realMinutes: number;
    totalDays: number;
    realDays: number;
}>();
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <Tooltip>
            <TooltipTrigger as-child>
                <StatItem class="cursor-help">
                    <StatItemLabel>
                        <StatItemIcon><ClockIcon /></StatItemIcon>
                        {{ t('app.stats.total_hours') }}
                    </StatItemLabel>
                    <StatItemValue :value="formatMinutes(props.totalMinutes)" muted />
                </StatItem>
            </TooltipTrigger>
            <TooltipContent side="bottom" align="start" class="border-border shadow-xl">
                <div class="grid grid-cols-[1fr_auto_auto] items-baseline gap-x-3 gap-y-1 text-xs">
                    <span class="text-muted-foreground">{{ t('app.stats.total_hours') }}</span>
                    <span class="font-mono font-semibold">{{ formatMinutes(props.totalMinutes) }}</span>
                    <span class="font-mono text-muted-foreground">{{ t('app.stats.days_equivalent', { count: formatDays(props.totalDays) }) }}</span>

                    <span class="text-muted-foreground">{{ t('app.stats.real_hours') }}</span>
                    <span class="font-mono font-semibold">{{ formatMinutes(props.realMinutes) }}</span>
                    <span class="font-mono text-muted-foreground">{{ t('app.stats.days_equivalent', { count: formatDays(props.realDays) }) }}</span>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
