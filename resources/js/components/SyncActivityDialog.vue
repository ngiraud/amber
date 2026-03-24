<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CalendarDaysIcon, CheckCircle2Icon, CircleIcon, Loader2Icon, XCircleIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useDateFormat } from '@/composables/useDateFormat';
import { t } from '@/composables/useTranslation';
import { reconstruct as reconstructRoute, sync as syncRoute } from '@/routes/activity';

type SourceType = { value: string; label: string; color: string };
type SourceProgress = {
    value: string;
    label: string;
    status: 'waiting' | 'scanning' | 'done' | 'error';
    count: number | null;
    errorMessage: string | null;
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
const totalSessionsCount = ref(0);
const syncedSince = ref<string>('');

const today = new Date().toISOString().slice(0, 10);

const periodOptions = computed((): { value: Period; label: string }[] => [
    { value: 'today', label: t('app.dashboard.today') },
    { value: 'yesterday', label: t('app.sync.yesterday') },
    { value: 'month', label: t('app.sync.start_of_month') },
    { value: 'custom', label: t('app.sync.custom_range') },
]);

const periodRangeLabel = computed(() => {
    const now = new Date();

    if (period.value === 'today') {
        return formatDateLong(now);
    }

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
    totalSessionsCount.value = 0;
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
    progress.value = sources.map((s) => ({ value: s.value, label: s.label, status: 'scanning' as const, count: null, errorMessage: null }));

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

                const data = response.ok ? ((await response.json()) as { count: number; source_errors: string[] }) : null;
                progress.value[i].status = data !== null ? 'done' : 'error';
                progress.value[i].count = data?.count ?? null;
                progress.value[i].errorMessage = data?.source_errors?.[0] ?? null;

                if (data !== null) {
                    totalCount.value += data.count;
                }
            } catch {
                progress.value[i].status = 'error';
            }
        }),
    );

    if (totalCount.value > 0) {
        const response = await fetch(reconstructRoute().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ since: syncedSince.value }),
        });

        if (response.ok) {
            const data = (await response.json()) as { sessions_count: number };
            totalSessionsCount.value = data.sessions_count;
        }
    }

    phase.value = 'done';
}

defineExpose({ show });
</script>

<template>
    <Dialog :open="open" @update:open="phase !== 'syncing' ? (open = $event) : undefined">
        <DialogContent class="sm:max-w-lg" :show-close-button="phase !== 'syncing'">
            <DialogHeader>
                <DialogTitle>{{ phase === 'done' ? t('app.sync.complete') : t('app.sync.title') }}</DialogTitle>
                <DialogDescription v-if="phase === 'config'">{{ t('app.sync.config_description') }}</DialogDescription>
                <DialogDescription v-else-if="phase === 'syncing'">{{ t('app.sync.scanning_description') }}</DialogDescription>
                <DialogDescription v-else-if="totalCount === 0">{{ t('app.sync.no_events_found', { period: periodRangeLabel }) }}</DialogDescription>
            </DialogHeader>

            <!-- Config phase -->
            <div v-if="phase === 'config'" class="flex flex-col gap-6 pt-2">
                <div class="flex flex-col gap-3">
                    <Label>{{ t('app.sync.period') }}</Label>
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
                            <Label class="text-xs text-muted-foreground">{{ t('app.report.from') }}</Label>
                            <DatePicker v-model="customFrom" :max="customTo" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs text-muted-foreground">{{ t('app.report.to') }}</Label>
                            <DatePicker v-model="customTo" :min="customFrom" :max="today" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <Label>{{ t('app.settings.sources') }}</Label>
                        <button
                            class="text-xs text-primary hover:underline"
                            @click="selectedSources = selectedSources.length === enabledSources.length ? [] : enabledSources.map((s) => s.value)"
                        >
                            {{ selectedSources.length === enabledSources.length ? t('app.sync.deselect_all') : t('app.sync.select_all') }}
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
                        {{ t('app.sync.no_sources') }}
                    </p>
                </div>

                <div class="flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                    <CalendarDaysIcon class="size-3.5 shrink-0" />
                    <span
                        >{{ t('app.sync.will_scan') }} <span class="font-medium text-foreground">{{ periodRangeLabel }}</span></span
                    >
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="ghost" size="sm" @click="open = false">{{ t('app.common.cancel') }}</Button>
                    <Button type="button" size="sm" :disabled="selectedSources.length === 0" @click="startSync">{{ t('app.sync.start') }}</Button>
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
                                <span class="text-muted-foreground">{{ t('app.sync.waiting') }}</span>
                            </template>
                            <template v-else-if="item.status === 'scanning'">
                                <Loader2Icon class="size-3.5 animate-spin" />
                                <span>{{ t('app.sync.scanning') }}</span>
                            </template>
                            <template v-else-if="item.status === 'done'">
                                <CheckCircle2Icon class="size-3.5" :class="item.count! > 0 ? 'text-green-500' : 'text-muted-foreground'" />
                                <span :class="item.count! > 0 ? 'font-medium' : 'text-muted-foreground'">
                                    {{ item.count === 0 ? t('app.sync.no_new_events') : t('app.sync.new_events', { count: item.count ?? 0 }) }}
                                </span>
                            </template>
                            <template v-else>
                                <XCircleIcon class="size-3.5 shrink-0 text-destructive" />
                                <span class="text-destructive">{{ item.errorMessage ?? t('app.common.error') }}</span>
                            </template>
                        </span>
                    </div>
                </div>

                <template v-if="phase === 'done' && totalCount > 0">
                    <div class="rounded-md border bg-muted/40 px-4 py-3">
                        <p class="text-sm font-medium">
                            {{ t('app.sync.new_events', { count: totalCount }) }} {{ t('app.sync.found_for') }}
                            <span class="text-muted-foreground">{{ periodRangeLabel }}</span>
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('app.sync.sessions_reconstructed', { count: totalSessionsCount }) }}
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <Button type="button" variant="ghost" size="sm" @click="open = false">{{ t('app.common.close') }}</Button>
                    </div>
                </template>

                <div v-else-if="phase === 'done'" class="flex justify-end">
                    <Button type="button" variant="ghost" size="sm" @click="open = false">{{ t('app.common.close') }}</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
