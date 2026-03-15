<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import FolderPathInput from '@/components/FolderPathInput.vue';
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
const name = ref('');
const localPath = ref('');

function onPick(path: string): void {
    if (!name.value) {
        name.value = path.split('/').filter(Boolean).pop() ?? '';
    }
}

function reset(): void {
    name.value = '';
    localPath.value = '';
}
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
                @success="() => { open = false; reset(); }"
            >
                <InputField label="Local path" :error="errors.local_path">
                    <FolderPathInput
                        v-model="localPath"
                        name="local_path"
                        placeholder="/Users/me/code/my-repo"
                        @pick="onPick"
                    />
                </InputField>

                <InputField label="Repository name" :error="errors.name">
                    <Input v-model="name" name="name" type="text" placeholder="my-repo" />
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
