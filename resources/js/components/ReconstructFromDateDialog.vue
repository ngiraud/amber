<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as sessionRoutes from '@/routes/sessions';

const open = ref(false);
const fromDate = ref('');
const mode = ref<'gaps' | 'replace'>('gaps');
const processing = ref(false);

function show(): void {
    fromDate.value = '';
    mode.value = 'gaps';
    open.value = true;
}

function submit(): void {
    if (!fromDate.value) {
        return;
    }

    processing.value = true;

    router.post(
        sessionRoutes.reconstructFrom().url,
        { from_date: fromDate.value, mode: mode.value },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                open.value = false;
            },
        },
    );
}

defineExpose({ show });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Reconstruct since a date</DialogTitle>
                <DialogDescription> Reconstruct sessions for every day from the selected date to today. </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4 pt-2">
                <div class="flex flex-col gap-2">
                    <Label for="from-date">
                        <CalendarIcon class="mr-1 inline-block size-3.5 text-muted-foreground" />
                        Starting from
                    </Label>
                    <Input
                        id="from-date"
                        v-model="fromDate"
                        type="date"
                        class="dark:[color-scheme:dark]"
                        :max="new Date().toISOString().slice(0, 10)"
                        required
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <Label>Mode</Label>
                    <div class="flex flex-col gap-2">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            :class="{ 'border-primary bg-primary/5': mode === 'gaps' }"
                        >
                            <input v-model="mode" type="radio" value="gaps" class="mt-0.5 accent-primary" />
                            <div>
                                <p class="text-sm font-medium">Fill gaps</p>
                                <p class="text-sm text-muted-foreground">Add missing sessions without touching existing ones.</p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            :class="{ 'border-primary bg-primary/5': mode === 'replace' }"
                        >
                            <input v-model="mode" type="radio" value="replace" class="mt-0.5 accent-primary" />
                            <div>
                                <p class="text-sm font-medium">Rebuild auto sessions</p>
                                <p class="text-sm text-muted-foreground">Delete auto-generated sessions and reconstruct each day from scratch.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button variant="ghost" size="sm" @click="open = false">Cancel</Button>
                <Button size="sm" :disabled="!fromDate || processing" @click="submit">
                    {{ processing ? 'Reconstructing…' : 'Reconstruct' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
