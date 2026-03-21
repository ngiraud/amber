<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Loader2Icon, PlusIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { t } from '@/composables/useTranslation';
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

const title = computed(() => (props.batch ? t('app.timeline.reconstruct_since_date') : t('app.timeline.reconstruct_sessions')));

const description = computed(() => {
    if (props.batch) {
        return t('app.timeline.reconstruct_since_description');
    }

    return props.hasSessions ? t('app.timeline.reconstruct_existing_description') : t('app.timeline.reconstruct_new_description');
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
                    <InputField :label="t('app.timeline.starting_from')" :error="errors.from_date" required>
                        <DatePicker v-model="fromDate" :max="new Date().toISOString().slice(0, 10)" />
                        <input type="hidden" name="from_date" :value="fromDate" />
                    </InputField>
                </template>
                <template v-else>
                    <input type="hidden" name="date" :value="date" />
                </template>

                <input type="hidden" name="mode" :value="mode" />

                <div class="flex flex-col gap-2">
                    <Label>{{ t('app.common.mode') }}</Label>
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
                                <ItemTitle>{{ t('app.timeline.fill_gaps') }}</ItemTitle>
                                <ItemDescription>{{ t('app.timeline.fill_gaps_description') }}</ItemDescription>
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
                                <ItemTitle>{{ t('app.timeline.rebuild_sessions') }}</ItemTitle>
                                <ItemDescription>{{ t('app.timeline.rebuild_sessions_description') }}</ItemDescription>
                            </ItemContent>
                            <RadioGroupItem value="replace" />
                        </Item>
                    </RadioGroup>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="ghost" size="sm" @click="open = false" :disabled="processing">{{ t('app.common.cancel') }}</Button>
                    <Button type="submit" size="sm" :disabled="processing || (batch && !fromDate)">
                        <Loader2Icon v-if="processing" class="mr-2 size-4 animate-spin" />
                        {{ processing ? t('app.timeline.reconstructing') : t('app.timeline.reconstruct') }}
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
