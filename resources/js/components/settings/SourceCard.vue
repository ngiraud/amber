<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { AlertCircleIcon, InfoIcon, Loader2, Settings2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { SourceDefinition } from '@/types';
import SourceConfigurationSheet from './SourceConfigurationSheet.vue';

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

// ── Clipboard ───────────────────────────────────────────────────────────────

const { copy } = useClipboard();

function handleCopy(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    const codeElement = target.closest('code');

    if (codeElement) {
        copy(codeElement.innerText);
        toast.info('Command copied to clipboard');
    }
}

// ── Helpers ─────────────────────────────────────────────────────────────────

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
        class="group group relative flex-col items-stretch rounded-xl bg-card p-4 text-card-foreground shadow-sm transition-all hover:ring-1 hover:ring-primary/30 dark:hover:ring-primary/40"
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
                            'relative size-2.5 rounded-full ring-2 ring-card transition-all duration-300',
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

                <Switch
                    :model-value="form.enabled"
                    :disabled="form.processing"
                    class="data-[state=checked]:bg-primary"
                    @update:model-value="onToggle"
                    @click.stop
                />
            </ItemActions>
        </div>

        <!-- Requirements Container -->
        <div
            :class="
                cn(
                    'group/requirements mt-4 flex flex-col gap-2 rounded-lg border border-dashed p-3 text-[11px] transition-all duration-300',
                    hasError
                        ? 'shake-1 animate-in border-destructive/50 bg-destructive/5 text-destructive dark:border-destructive/40'
                        : 'border-primary/20 bg-muted/40 text-muted-foreground group-hover:border-primary/40 group-hover:bg-muted/60 dark:border-primary/10 dark:bg-muted/20 dark:group-hover:border-primary/30',
                )
            "
        >
            <div
                :class="
                    cn(
                        'flex items-center gap-1.5 text-[9px] font-bold tracking-widest uppercase',
                        hasError ? 'text-destructive' : 'text-primary/80 dark:text-primary/60',
                    )
                "
            >
                <AlertCircleIcon v-if="hasError" class="size-3" />
                <InfoIcon v-else class="size-3" />
                {{ hasError ? 'Tool Not Found' : 'Setup Requirements' }}
            </div>

            <div
                class="relative leading-relaxed [&_code]:relative [&_code]:mt-1.5 [&_code]:block [&_code]:cursor-pointer [&_code]:rounded [&_code]:border [&_code]:px-2 [&_code]:py-1.5 [&_code]:font-mono [&_code]:text-[10px] [&_code]:shadow-xs [&_code]:transition-all [&_code]:duration-200 [&_code::after]:absolute [&_code::after]:top-1/2 [&_code::after]:right-2 [&_code::after]:-translate-y-1/2 [&_code::after]:size-3 [&_code::after]:bg-contain [&_code::after]:bg-no-repeat [&_code::after]:opacity-0 [&_code::after]:grayscale [&_code::after]:brightness-50 [&_code::after]:transition-opacity [&_code::after]:duration-200 [&_code:hover::after]:opacity-40 dark:[&_code::after]:grayscale-0 dark:[&_code::after]:brightness-150 dark:[&_code::after]:invert"
                :class="
                    cn(
                        hasError
                            ? '[&_code]:border-destructive/20 [&_code]:bg-destructive/10 [&_code]:text-destructive'
                            : '[&_code]:bg-background/80 [&_code]:text-foreground dark:[&_code]:bg-background/40',
                    )
                "
                @click="handleCopy"
                v-html="source.requirements"
            />
        </div>
    </Item>
</template>

<style scoped>
:deep(code::after) {
    content: '';
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect width='14' height='14' x='8' y='8' rx='2' ry='2'%3E%3C/rect%3E%3Cpath d='M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2'%3E%3C/path%3E%3C/svg%3E");
}
</style>
