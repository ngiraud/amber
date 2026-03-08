<script setup lang="ts">
import { computed } from 'vue';
import { Form, router, usePage } from '@inertiajs/vue3';
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
    <div
        class="relative flex h-9 w-full shrink-0 select-none items-center bg-sidebar"
        style="-webkit-app-region: drag"
    >
        <!-- Left: spacer for macOS traffic lights -->
        <div class="w-[70px] shrink-0" />

        <!-- Center: absolutely positioned so it's always visually centered -->
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <template v-if="activeSession">
                <div class="flex items-center gap-1.5">
                    <span class="size-1.5 animate-pulse rounded-full bg-green-500" />
                    <span class="max-w-xs truncate text-xs font-medium text-foreground/80">
                        {{ activeSession.project?.name
                        }}<span v-if="activeSession.project?.client" class="font-normal text-muted-foreground">
                            — {{ activeSession.project.client.name }}</span>
                    </span>
                </div>
            </template>
            <template v-else>
                <div v-if="breadcrumb?.length" class="flex items-center gap-1.5">
                    <template v-for="(item, index) in breadcrumb" :key="index">
                        <span v-if="index > 0" class="text-xs text-muted-foreground/40">›</span>
                        <span
                            :class="[
                                'text-xs',
                                index < breadcrumb.length - 1
                                    ? 'text-muted-foreground'
                                    : 'font-medium text-foreground/80',
                            ]"
                        >{{ item }}</span>
                    </template>
                </div>
                <span v-else class="text-xs font-medium text-muted-foreground">
                    {{ title ?? 'Activity Record' }}
                </span>
            </template>
        </div>

        <!-- Right: timer + stop button when session is active -->
        <div class="ml-auto flex items-center gap-2 pr-3" style="-webkit-app-region: no-drag">
            <template v-if="activeSession">
                <span class="font-mono text-xs tabular-nums text-muted-foreground">
                    <SessionTimer :started-at="activeSession.started_at" />
                </span>
                <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
                    <Button variant="destructive" size="sm" class="h-6 px-2 text-[10px]" @click="submit">
                        Stop
                    </Button>
                </Form>
            </template>
        </div>
    </div>
</template>
