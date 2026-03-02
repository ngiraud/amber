<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import MonthCalendar from '@/components/MonthCalendar.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as timelineRoutes from '@/routes/timeline';
import type { TimelineDay } from '@/types';

const props = defineProps<{
    year: number;
    month: number;
    days: TimelineDay[];
}>();

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const monthLabel = computed(() => `${MONTHS[props.month - 1]} ${props.year}`);

function navigate(direction: -1 | 1): void {
    let month = props.month + direction;
    let year = props.year;

    if (month < 1) {
        month = 12;
        year -= 1;
    } else if (month > 12) {
        month = 1;
        year += 1;
    }

    router.get(timelineRoutes.index({ query: { year, month } }).url);
}

function selectDay(date: string): void {
    router.get(timelineRoutes.show({ date: date }).url);
}
</script>

<template>
    <AppLayout title="Timeline">
        <template #header>
            <PageHeader title="Timeline">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="ghost" size="icon" @click="navigate(-1)">
                            <ChevronLeftIcon class="size-4" />
                        </Button>
                        <span class="min-w-36 text-center text-sm font-medium">{{ monthLabel }}</span>
                        <Button variant="ghost" size="icon" @click="navigate(1)">
                            <ChevronRightIcon class="size-4" />
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <MonthCalendar :year="year" :month="month" :days="days" class="mt-2" @select="selectDay" />
    </AppLayout>
</template>
