<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Loader2Icon, PlusIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { reconstruct, reconstructFrom } from '@/routes/sessions';

const props = withDefaults(
    defineProps<{
        date?: string;
        hasSessions?: boolean;
        batch?: boolean;
    }>(),
    {
        date: '',
        hasSessions: false,
        batch: false,
    },
);

const open = ref(false);
const fromDate = ref(props.date);
const mode = ref<'gaps' | 'replace'>('gaps');

function show(initialFromDate?: string): void {
    if (props.batch && typeof initialFromDate === 'string') {
        fromDate.value = initialFromDate;
    }
    mode.value = 'gaps';
    open.value = true;
}

defineExpose({ show });

const title = computed(() => (props.batch ? 'Reconstruct since a date' : 'Reconstruct sessions'));

const description = computed(() => {
    if (props.batch) {
        return 'Reconstruct sessions for every day from the selected date to today.';
    }

    return props.hasSessions
        ? 'There are existing sessions for this day. How would you like to reconstruct?'
        : 'Configure how you would like to reconstruct sessions for this day.';
});

const action = computed(() => (props.batch ? reconstructFrom() : reconstruct()));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child @click="show">
            <slot />
        </DialogTrigger>

        <DialogContent class="sm:max-w-md" :show-close-button="false">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <Form :action="action" class="flex flex-col gap-4 pt-2" #default="{ errors, processing }" @success="() => (open = false)">
                <template v-if="batch">
                    <InputField label="Starting from" :error="errors.from_date" required>
                        <Input
                            v-model="fromDate"
                            name="from_date"
                            type="date"
                            class="dark:[color-scheme:dark]"
                            :max="new Date().toISOString().slice(0, 10)"
                        />
                    </InputField>
                </template>
                <template v-else>
                    <input type="hidden" name="date" :value="date" />
                </template>

                <input type="hidden" name="mode" :value="mode" />

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
                                <ItemDescription>Delete auto-generated sessions and reconstruct from scratch.</ItemDescription>
                            </ItemContent>
                            <RadioGroupItem value="replace" />
                        </Item>
                    </RadioGroup>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="ghost" size="sm" @click="open = false" :disabled="processing">Cancel</Button>
                    <Button type="submit" size="sm" :disabled="processing || (batch && !fromDate)">
                        <Loader2Icon v-if="processing" class="mr-2 size-4 animate-spin" />
                        {{ processing ? 'Reconstructing…' : 'Reconstruct' }}
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
