<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { PlusIcon, RefreshCwIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { reconstruct } from '@/routes/sessions';

const props = defineProps<{
    date: string;
    hasSessions: boolean;
}>();

const open = ref(false);

function handleClick(): void {
    if (props.hasSessions) {
        open.value = true;
    } else {
        router.post(reconstruct().url, { date: props.date, mode: 'gaps' }, { preserveScroll: true });
    }
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
                <Form :action="reconstruct()" @success="() => (open = false)">
                    <input type="hidden" name="date" :value="date" />
                    <input type="hidden" name="mode" value="gaps" />
                    <Item as="button" type="submit" variant="outline" class="w-full cursor-pointer text-left transition-colors hover:bg-muted/50">
                        <ItemMedia>
                            <PlusIcon class="size-4 text-muted-foreground" />
                        </ItemMedia>
                        <ItemContent>
                            <ItemTitle>Fill gaps</ItemTitle>
                            <ItemDescription>Add missing sessions without touching existing ones.</ItemDescription>
                        </ItemContent>
                    </Item>
                </Form>

                <Form :action="reconstruct()" @success="() => (open = false)">
                    <input type="hidden" name="date" :value="date" />
                    <input type="hidden" name="mode" value="replace" />
                    <Item as="button" type="submit" variant="outline" class="w-full cursor-pointer text-left transition-colors hover:bg-muted/50">
                        <ItemMedia>
                            <RefreshCwIcon class="size-4 text-muted-foreground" />
                        </ItemMedia>
                        <ItemContent>
                            <ItemTitle>Rebuild auto sessions</ItemTitle>
                            <ItemDescription>Delete auto-generated sessions and reconstruct from scratch.</ItemDescription>
                        </ItemContent>
                    </Item>
                </Form>
            </div>

            <div class="flex justify-end pt-2">
                <Button variant="ghost" size="sm" @click="open = false">Cancel</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
