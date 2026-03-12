<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertCircleIcon, InfoIcon, Loader2, Settings2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { SourceDefinition } from '@/types';
import SourceConfigurationSheet from './SourceConfigurationSheet.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    source: SourceDefinition;
}>();

// ── Form ────────────────────────────────────────────────────────────────────

const form = useForm({ enabled: Boolean(props.source.config.enabled) });
const isSheetOpen = ref(false);

const errors = computed(() => form.errors as Record<string, string | undefined>);
const hasError = computed(() => !!errors.value[`${props.source.value}.enabled`]);

function save(visitOptions: { preserveScroll?: boolean; onSuccess?: () => void } = {}): void {
    const { onSuccess, ...rest } = visitOptions;

    form.transform(() => ({
        [props.source.value]: { enabled: form.enabled },
    })).submit(sourcesRoutes.update(props.source.value), {
        ...rest,
        onSuccess: () => {
            onSuccess?.();
        },
        onError: (serverErrors: Record<string, string>) => {
            if (serverErrors[`${props.source.value}.enabled`]) {
                form.enabled = Boolean(props.source.config.enabled);
            }
        },
    });
}

// ── Toggle ──────────────────────────────────────────────────────────────────

function onToggle(val: boolean): void {
    form.enabled = val;
    save({ preserveScroll: true });
}

// Extract color from Tailwind class (e.g. text-[#DE7356] or text-green-400)
const indicatorColor = computed(() => {
    const colorClass = props.source.color;
    if (colorClass.startsWith('text-[')) {
        return colorClass.match(/\[(.*?)\]/)?.[1];
    }
    return undefined;
});

const indicatorClass = computed(() => {
    if (indicatorColor.value) return '';
    return props.source.color.replace('text-', 'bg-');
});
</script>

<template>
    <Item
        variant="outline"
        size="sm"
        class="bg-card text-card-foreground relative flex-col items-stretch rounded-xl p-4 shadow-sm transition-all hover:ring-1 hover:ring-primary/30 dark:hover:ring-primary/40"
    >
        <div class="flex items-start gap-3">
            <ItemMedia class="relative pt-1.5">
                <!-- Ping Animation (Active only) -->
                <div
                    v-if="source.config.enabled"
                    class="absolute inset-0 top-1.5 size-2.5 animate-ping rounded-full opacity-75"
                    :class="indicatorClass"
                    :style="indicatorColor ? { backgroundColor: indicatorColor } : {}"
                />

                <!-- Static Indicator Dot -->
                <div
                    :class="
                        cn(
                            'relative size-2.5 rounded-full ring-2 ring-background transition-all duration-300',
                            source.config.enabled ? indicatorClass : 'bg-muted-foreground/30',
                        )
                    "
                    :style="source.config.enabled && indicatorColor ? { backgroundColor: indicatorColor } : {}"
                />
            </ItemMedia>

            <ItemContent class="flex-1 gap-1">
                <ItemTitle class="text-base font-semibold">{{ source.label }}</ItemTitle>
                <ItemDescription class="line-clamp-none text-xs leading-relaxed opacity-85 dark:opacity-75">
                    {{ source.description }}
                </ItemDescription>
            </ItemContent>

            <ItemActions class="gap-3 pt-0.5">
                <Loader2 v-if="form.processing" class="size-4 animate-spin text-muted-foreground" />

                <SourceConfigurationSheet v-model:open="isSheetOpen" :source="source">
                    <Button
                        v-if="form.enabled && source.fields.length > 0"
                        variant="ghost"
                        size="icon"
                        class="size-8 rounded-full transition-all hover:bg-accent hover:text-accent-foreground active:scale-95"
                    >
                        <Settings2 class="size-4" />
                    </Button>
                </SourceConfigurationSheet>

                <Switch :model-value="form.enabled" :disabled="form.processing" class="data-[state=checked]:bg-primary" @update:model-value="onToggle" @click.stop />
            </ItemActions>
        </div>

        <!-- Requirements & Error Handling -->
        <div
            :class="cn(
                'mt-4 flex flex-col gap-2 rounded-lg border border-dashed p-3 text-[11px] transition-all duration-300',
                hasError 
                    ? 'border-destructive/50 bg-destructive/5 text-destructive animate-in shake-1 dark:border-destructive/40' 
                    : 'border-primary/20 bg-muted/40 text-muted-foreground hover:border-primary/40 hover:bg-muted/60 dark:border-primary/10 dark:bg-muted/20 dark:hover:border-primary/30'
            )"
        >
            <div 
                :class="cn(
                    'flex items-center gap-1.5 font-bold uppercase tracking-widest text-[9px]',
                    hasError ? 'text-destructive' : 'text-primary/80 dark:text-primary/60'
                )"
            >
                <AlertCircleIcon v-if="hasError" class="size-3" />
                <InfoIcon v-else class="size-3" />
                {{ hasError ? 'Tool Not Found' : 'Setup Requirements' }}
            </div>
            
            <div
                class="leading-relaxed [&_code]:mt-1.5 [&_code]:block [&_code]:rounded [&_code]:border [&_code]:px-2 [&_code]:py-1.5 [&_code]:font-mono [&_code]:text-[10px] [&_code]:shadow-xs"
                :class="cn(
                    hasError 
                        ? '[&_code]:border-destructive/20 [&_code]:bg-destructive/10 [&_code]:text-destructive' 
                        : '[&_code]:bg-background/80 [&_code]:text-foreground dark:[&_code]:bg-background/20'
                )"
                v-html="source.requirements"
            />
        </div>
    </Item>
</template>
