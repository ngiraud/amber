<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PlusIcon, RefreshCwIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import * as sessionRoutes from '@/routes/sessions';

const props = defineProps<{
    date: string;
    hasSessions: boolean;
}>();

const open = ref(false);

function handleClick(): void {
    if (props.hasSessions) {
        open.value = true;
    } else {
        submit('gaps');
    }
}

function submit(mode: 'gaps' | 'replace'): void {
    open.value = false;
    router.post(sessionRoutes.reconstruct().url, { date: props.date, mode }, { preserveScroll: true });
}
</script>

<template>
    <slot :handle-click="handleClick" />

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md" :show-close-button="false">
            <DialogHeader>
                <DialogTitle>Reconstruct sessions</DialogTitle>
                <DialogDescription> There are existing sessions for this day. How would you like to reconstruct? </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-3 pt-2">
                <button class="flex items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:bg-muted/50" @click="submit('gaps')">
                    <PlusIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">Fill gaps</p>
                        <p class="text-sm text-muted-foreground">Add missing sessions without touching existing ones.</p>
                    </div>
                </button>

                <button class="flex items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:bg-muted/50" @click="submit('replace')">
                    <RefreshCwIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">Rebuild auto sessions</p>
                        <p class="text-sm text-muted-foreground">Delete auto-generated sessions and reconstruct from scratch.</p>
                    </div>
                </button>
            </div>

            <div class="flex justify-end pt-2">
                <Button variant="ghost" size="sm" @click="open = false">Cancel</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
