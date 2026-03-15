<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as clientRoutes from '@/routes/clients';
import type { Client } from '@/types';

const props = defineProps<{
    client?: Client;
}>();

const open = defineModel<boolean>('open', { default: false });
const isEditing = computed(() => !!props.client);
const action = computed(() => (isEditing.value ? clientRoutes.update(props.client!) : clientRoutes.store()));
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit client' : 'New client' }}</SheetTitle>
            </SheetHeader>

            <Form class="flex flex-col gap-5 px-4 py-2" :action="action" #default="{ errors, processing }" @success="() => (open = false)">
                <InputField label="Name" :error="errors.name" required>
                    <Input name="name" type="text" :default-value="client?.name" :placeholder="isEditing ? undefined : 'Acme Corp'" autofocus />
                </InputField>

                <InputField label="Notes" :error="errors.notes">
                    <Textarea
                        name="notes"
                        rows="4"
                        :default-value="client?.notes ?? undefined"
                        :placeholder="isEditing ? undefined : 'Optional notes about this client…'"
                    />
                </InputField>

                <SheetFooter class="px-0">
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create client' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
