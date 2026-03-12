<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import repositories from '@/routes/projects/repositories';
import type { Project } from '@/types';

defineProps<{
    project: Project;
}>();

const open = ref(false);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Add repository</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 px-4 py-2"
                :action="repositories.store(project)"
                reset-on-success
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <InputField label="Repository name" :error="errors.name">
                    <Input name="name" type="text" placeholder="my-repo" autofocus />
                </InputField>

                <InputField label="Local path" :error="errors.local_path">
                    <Input name="local_path" type="text" placeholder="/Users/me/code/my-repo" class="font-mono" />
                </InputField>

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? 'Adding…' : 'Add repository' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
