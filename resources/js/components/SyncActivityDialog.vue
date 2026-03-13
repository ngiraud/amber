<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowRightIcon, CalendarDaysIcon, CheckCircle2Icon, CircleIcon, Loader2Icon, XCircleIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useDateFormat } from '@/composables/useDateFormat';
import { sync as syncRoute } from '@/routes/activity';
import * as timelineRoutes from '@/routes/timeline';

type SourceType = { value: string; label: string; color: string };
type SourceProgress = {
    value: string;
    label: string;
    status: 'waiting' | 'scanning' | 'done' | 'error';
    count: number | null;
};
type Period = 'today' | 'yesterday' | 'month' | 'custom';
type Phase = 'config' | 'syncing' | 'done';

const page = usePage<{ enabledSourceTypes: SourceType[] }>();
const enabledSources = computed(() => page.props.enabledSourceTypes ?? []);

const { toLocalDateString, formatDateLong, formatDateShort } = useDateFormat();

const open = ref(false);
const phase = ref<Phase>('config');
const period = ref<Period>('today');
const customFrom = ref(new Date().toISOString().slice(0, 10));
const customTo = ref(new Date().toISOString().slice(0, 10));
const selectedSources = ref<string[]>([]);
const progress = ref<SourceProgress[]>([]);
const totalCount = ref(0);
const syncedSince = ref('');

const today = new Date().toISOString().slice(0, 10);

const periodOptions: { value: Period; label: string }[] = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'month', label: 'Start of month' },
    { value: 'custom', label: 'Custom range' },
];

const periodRangeLabel = computed(() => {
    const now = new Date();
    if (period.value === 'today') return formatDateLong(now);
    if (period.value === 'yesterday') {
        return formatDateLong(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1));
    }
    if (period.value === 'month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        return `${formatDateShort(start)} – ${formatDateLong(now)}`;
    }
    if (customFrom.value && customTo.value) {
        const from = new Date(customFrom.value + 'T00:00:00');
        const to = new Date(customTo.value + 'T00:00:00');
        return from.getTime() === to.getTime() ? formatDateLong(from) : `${formatDateShort(from)} – ${formatDateLong(to)}`;
    }
    return '';
});

function show(): void {
    selectedSources.value = enabledSources.value.map((s) => s.value);
    phase.value = 'config';
    totalCount.value = 0;
    syncedSince.value = '';
    open.value = true;
}

function toggleSource(value: string): void {
    const index = selectedSources.value.indexOf(value);
    if (index > -1) {
        selectedSources.value.splice(index, 1);
    } else {
        selectedSources.value.push(value);
    }
}

function getDateRange(): { since: string; until: string; sinceLocal: string } {
    const now = new Date();
    const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString();
    const endOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59, 999).toISOString();

    if (period.value === 'today') {
        return { since: startOfDay(now), until: endOfDay(now), sinceLocal: toLocalDateString(now) };
    }
    if (period.value === 'yesterday') {
        const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
        return { since: startOfDay(yesterday), until: endOfDay(yesterday), sinceLocal: toLocalDateString(yesterday) };
    }
    if (period.value === 'month') {
        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
        return { since: startOfDay(monthStart), until: endOfDay(now), sinceLocal: toLocalDateString(monthStart) };
    }
    return {
        since: new Date(customFrom.value + 'T00:00:00').toISOString(),
        until: new Date(customTo.value + 'T23:59:59').toISOString(),
        sinceLocal: customFrom.value,
    };
}

function getCsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

async function startSync(): Promise<void> {
    const { since, until, sinceLocal } = getDateRange();
    const sources = enabledSources.value.filter((s) => selectedSources.value.includes(s.value));

    phase.value = 'syncing';
    totalCount.value = 0;
    syncedSince.value = sinceLocal;
    progress.value = sources.map((s) => ({ value: s.value, label: s.label, status: 'scanning' as const, count: null }));

    await Promise.all(
        progress.value.map(async (item, i) => {
            try {
                const response = await fetch(syncRoute().url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({ since, until, source_type: item.value }),
                });

                const data = response.ok ? ((await response.json()) as { count: number }) : null;
                progress.value[i].status = data !== null ? 'done' : 'error';
                progress.value[i].count = data?.count ?? null;
                if (data !== null) totalCount.value += data.count;
            } catch {
                progress.value[i].status = 'error';
            }
        }),
    );

    phase.value = 'done';
}

function reconstructSessions(): void {
    open.value = false;
    router.visit(timelineRoutes.index({ query: { reconstruct_from: syncedSince.value } }));
}

defineExpose({ show });
</script>

<template>
    <Dialog :open="open" @update:open="phase !== 'syncing' ? (open = $event) : undefined">
        <DialogContent class="sm:max-w-lg" :show-close-button="phase !== 'syncing'">
            <DialogHeader>
                <DialogTitle>{{ phase === 'done' ? 'Sync complete' : 'Sync Activity Sources' }}</DialogTitle>
                <DialogDescription v-if="phase === 'config'">Select the period and sources you want to scan for activity.</DialogDescription>
                <DialogDescription v-else-if="phase === 'syncing'">Scanning your sources, please wait…</DialogDescription>
                <DialogDescription v-else-if="totalCount === 0">No new events found for {{ periodRangeLabel }}.</DialogDescription>
            </DialogHeader>

            <!-- Config phase -->
            <div v-if="phase === 'config'" class="flex flex-col gap-6 pt-2">
                <div class="flex flex-col gap-3">
                    <Label>Period</Label>
                    <RadioGroup v-model="period" class="grid grid-cols-2 gap-2">
                        <label
                            v-for="opt in periodOptions"
                            :key="opt.value"
                            class="flex cursor-pointer items-center justify-between rounded-md border border-muted bg-popover p-3 hover:bg-accent hover:text-accent-foreground [&:has([data-state=checked])]:border-primary"
                        >
                            <span class="text-sm font-medium">{{ opt.label }}</span>
                            <RadioGroupItem :value="opt.value" class="sr-only" />
                        </label>
                    </RadioGroup>

                    <div v-if="period === 'custom'" class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs text-muted-foreground">From</Label>
                            <Input v-model="customFrom" type="date" class="dark:scheme-dark" :max="customTo" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs text-muted-foreground">To</Label>
                            <Input v-model="customTo" type="date" class="dark:scheme-dark" :min="customFrom" :max="today" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <Label>Sources</Label>
                        <button
                            class="text-xs text-primary hover:underline"
                            @click="selectedSources = selectedSources.length === enabledSources.length ? [] : enabledSources.map((s) => s.value)"
                        >
                            {{ selectedSources.length === enabledSources.length ? 'Deselect all' : 'Select all' }}
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="source in enabledSources"
                            :key="source.value"
                            class="flex cursor-pointer items-center gap-2 rounded-md border border-muted p-2 transition-colors hover:bg-accent hover:text-accent-foreground"
                            @click="toggleSource(source.value)"
                        >
                            <Checkbox
                                :id="`source-${source.value}`"
                                :model-value="selectedSources.includes(source.value)"
                                @update:model-value="toggleSource(source.value)"
                            />
                            <label :for="`source-${source.value}`" class="cursor-pointer text-sm leading-none font-medium">{{ source.label }}</label>
                        </div>
                    </div>
                    <p v-if="enabledSources.length === 0" class="text-xs text-muted-foreground italic">
                        No sources enabled. Enable them in settings.
                    </p>
                </div>

                <div class="flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                    <CalendarDaysIcon class="size-3.5 shrink-0" />
                    <span
                        >Will scan: <span class="font-medium text-foreground">{{ periodRangeLabel }}</span></span
                    >
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="ghost" size="sm" @click="open = false">Cancel</Button>
                    <Button type="button" size="sm" :disabled="selectedSources.length === 0" @click="startSync">Start sync</Button>
                </div>
            </div>

            <!-- Syncing / Done phase -->
            <div v-else class="flex flex-col gap-4 pt-2">
                <div class="divide-y divide-border rounded-md border">
                    <div
                        v-for="item in progress"
                        :key="item.value"
                        class="flex items-center justify-between px-3 py-2.5 transition-colors"
                        :class="{ 'bg-muted/50': item.status === 'scanning' }"
                    >
                        <span class="text-sm font-medium">{{ item.label }}</span>
                        <span class="flex items-center gap-1.5 text-xs">
                            <template v-if="item.status === 'waiting'">
                                <CircleIcon class="size-3.5 text-muted-foreground" />
                                <span class="text-muted-foreground">Waiting</span>
                            </template>
                            <template v-else-if="item.status === 'scanning'">
                                <Loader2Icon class="size-3.5 animate-spin" />
                                <span>Scanning…</span>
                            </template>
                            <template v-else-if="item.status === 'done'">
                                <CheckCircle2Icon class="size-3.5" :class="item.count! > 0 ? 'text-green-500' : 'text-muted-foreground'" />
                                <span :class="item.count! > 0 ? 'font-medium' : 'text-muted-foreground'">
                                    {{ item.count === 0 ? 'No new events' : `${item.count} new ${item.count === 1 ? 'event' : 'events'}` }}
                                </span>
                            </template>
                            <template v-else>
                                <XCircleIcon class="size-3.5 text-destructive" />
                                <span class="text-destructive">Error</span>
                            </template>
                        </span>
                    </div>
                </div>

                <template v-if="phase === 'done' && totalCount > 0">
                    <div class="rounded-md border bg-muted/40 px-4 py-3">
                        <p class="text-sm font-medium">
                            {{ totalCount }} new {{ totalCount === 1 ? 'event' : 'events' }} found for
                            <span class="text-muted-foreground">{{ periodRangeLabel }}</span>
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Events are raw data — they need to be grouped into sessions to appear on your timeline. Reconstruct your sessions to
                            include them.
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="ghost" size="sm" @click="open = false">Close</Button>
                        <Button type="button" size="sm" @click="reconstructSessions">
                            <ArrowRightIcon class="mr-1.5 size-3.5" />
                            Reconstruct sessions
                        </Button>
                    </div>
                </template>

                <div v-else-if="phase === 'done'" class="flex justify-end">
                    <Button type="button" variant="ghost" size="sm" @click="open = false">Close</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
