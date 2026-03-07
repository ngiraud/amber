<script setup lang="ts">
import { Form, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SessionTimer from '@/components/SessionTimer.vue';
import { Button } from '@/components/ui/button';
import { useNativeEvent } from '@/composables/useNativeEvent';
import * as sessionRoutes from '@/routes/sessions';

useNativeEvent('App\\Events\\SessionStarted', () => router.reload({ only: ['activeSession'] }));
useNativeEvent('App\\Events\\SessionStopped', () => router.reload({ only: ['activeSession'] }));

const page = usePage();
const activeSession = computed(() => page.props.activeSession);
</script>

<template>
    <div v-if="activeSession" class="sticky top-0 z-10 flex items-center justify-between border-b bg-sidebar px-8 py-2.5 backdrop-blur-sm">
        <div class="flex items-center gap-3 text-sm">
            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500" />
            <span class="font-medium">{{ activeSession.project?.name }}</span>
            <span class="text-muted-foreground">
                <SessionTimer :started-at="activeSession.started_at" />
            </span>
        </div>

        <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
            <Button variant="destructive" size="sm" @click="submit">Stop</Button>
        </Form>
    </div>
</template>
