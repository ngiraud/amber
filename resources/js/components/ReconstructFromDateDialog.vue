<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PlusIcon, RefreshCwIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { reconstructFrom } from '@/routes/sessions';

const fromDate = ref('');
const mode = ref<'gaps' | 'replace'>('gaps');
const open = ref(false);

function show(): void {
    fromDate.value = '';
    mode.value = 'gaps';
    open.value = true;
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

            <Form :action="reconstructFrom()" class="flex flex-col gap-4 pt-2" #default="{ errors, processing }" @success="() => (open = false)">
                <input type="hidden" name="mode" :value="mode" />

                <InputField label="Starting from" :error="errors.from_date" required>
                    <Input
                        v-model="fromDate"
                        name="from_date"
                        type="date"
                        class="dark:[color-scheme:dark]"
                        :max="new Date().toISOString().slice(0, 10)"
                    />
                </InputField>

                <div class="flex flex-col gap-2">
                    <Label>Mode</Label>
                    <RadioGroup v-model="mode" class="gap-2">
                        <Item
                            as="label"
                            variant="outline"
                            class="cursor-pointer transition-colors hover:bg-muted/50"
                            :class="{ 'border-primary bg-primary/5': mode === 'gaps' }"
                        >
                            <ItemMedia>
                                <PlusIcon class="size-4 text-muted-foreground" />
                            </ItemMedia>
                            <ItemContent>
                                <ItemTitle>Fill gaps</ItemTitle>
                                <ItemDescription>Add missing sessions without touching existing ones.</ItemDescription>
                            </ItemContent>
                            <RadioGroupItem value="gaps" />
                        </Item>

                        <Item
                            as="label"
                            variant="outline"
                            class="cursor-pointer transition-colors hover:bg-muted/50"
                            :class="{ 'border-primary bg-primary/5': mode === 'replace' }"
                        >
                            <ItemMedia>
                                <RefreshCwIcon class="size-4 text-muted-foreground" />
                            </ItemMedia>
                            <ItemContent>
                                <ItemTitle>Rebuild auto sessions</ItemTitle>
                                <ItemDescription>Delete auto-generated sessions and reconstruct each day from scratch.</ItemDescription>
                            </ItemContent>
                            <RadioGroupItem value="replace" />
                        </Item>
                    </RadioGroup>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="ghost" size="sm" @click="open = false">Cancel</Button>
                    <Button type="submit" size="sm" :disabled="processing">
                        {{ processing ? 'Reconstructing…' : 'Reconstruct' }}
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
