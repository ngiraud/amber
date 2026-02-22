<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SessionTimer from '@/components/SessionTimer.vue';
import { Button } from '@/components/ui/button';
import * as sessionRoutes from '@/routes/sessions';

const page = usePage();
const activeSession = computed(() => page.props.activeSession);
</script>

<template>
    <div v-if="activeSession" class="flex items-center justify-between border-b bg-primary/5 px-8 py-2.5">
        <div class="flex items-center gap-3 text-sm">
            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500" />
            <span class="font-medium">{{ activeSession.project?.name }}</span>
            <span class="text-muted-foreground">
                <SessionTimer :started-at="activeSession.started_at" />
            </span>
        </div>

        <Form :action="sessionRoutes.stop(activeSession)" method="patch" #default="{ submit }">
            <Button variant="outline" size="sm" @click="submit">Stop</Button>
        </Form>
    </div>
</template>
