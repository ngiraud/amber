<script setup lang="ts">
import { Form, router, usePage } from '@inertiajs/vue3';
import { SquareIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import SessionTimer from '@/components/SessionTimer.vue';
import { Button } from '@/components/ui/button';
import { useNativeEvent } from '@/composables/useNativeEvent';
import * as sessionRoutes from '@/routes/sessions';

defineProps<{
    title?: string;
    breadcrumb?: string[];
}>();

useNativeEvent('App\\Events\\SessionStarted', () => router.reload({ only: ['activeSession'] }));
useNativeEvent('App\\Events\\SessionStopped', () => router.reload({ only: ['activeSession'] }));

const page = usePage();
const activeSession = computed(() => page.props.activeSession);
</script>

<template>
    <div class="flex h-9 w-full shrink-0 items-center bg-sidebar select-none" style="-webkit-app-region: drag">
        <!-- Left: spacer for macOS traffic lights -->
        <div class="w-[70px] shrink-0" />

        <!-- Center: always shows breadcrumb/title -->
        <div class="pointer-events-none flex flex-1 items-center justify-center">
            <div v-if="breadcrumb?.length" class="flex items-center gap-1.5">
                <template v-for="(item, index) in breadcrumb" :key="index">
                    <span v-if="index > 0" class="text-xs text-muted-foreground/40">›</span>
                    <span :class="['text-xs', index < breadcrumb.length - 1 ? 'text-muted-foreground' : 'font-medium text-foreground/80']">{{
                        item
                    }}</span>
                </template>
            </div>
            <span v-else class="text-xs font-medium text-muted-foreground">
                {{ title ?? 'Activity Record' }}
            </span>
        </div>

        <!-- Right: session info when active -->
        <div class="flex shrink-0 items-center gap-2 pr-3" style="-webkit-app-region: no-drag">
            <template v-if="activeSession">
                <span class="size-1.5 shrink-0 animate-pulse rounded-full bg-green-500" />
                <span class="max-w-[160px] truncate text-xs text-muted-foreground">
                    {{ activeSession.project?.client?.name
                    }}<span v-if="activeSession.project?.client && activeSession.project?.name"> — {{ activeSession.project.name }}</span>
                </span>
                <span class="shrink-0 font-mono text-xs text-muted-foreground tabular-nums">
                    <SessionTimer :started-at="activeSession.started_at" />
                </span>
                <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
                    <Button variant="ghost" size="sm" class="h-6 text-xs text-muted-foreground hover:text-white" @click="submit">
                        <SquareIcon class="size-3 fill-current" />
                        Stop
                    </Button>
                </Form>
            </template>
        </div>
    </div>
</template>
