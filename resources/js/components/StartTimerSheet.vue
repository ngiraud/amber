<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useOpenSessionDialog } from '@/composables/useOpenSessionDialog';
import * as sessionRoutes from '@/routes/sessions';

const page = usePage();
const projects = computed(() => page.props.projects ?? []);

const open = ref(false);

watch(open, (val) => {
    if (val) {
        router.reload({ only: ['projects'] });
    }
});

const { shouldOpen } = useOpenSessionDialog();
watch(shouldOpen, (val) => {
    if (val) {
        open.value = true;
        shouldOpen.value = false;
    }
});

const form = useForm({
    project_id: '',
    notes: '',
});

function submit(): void {
    form.submit(sessionRoutes.start(), {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger v-if="$slots.default" as-child>
            <slot />
        </SheetTrigger>

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Live Session</SheetTitle>
                <SheetDescription>
                    Track time for work that isn't automatically captured — meetings, code reviews, pair programming, training.
                </SheetDescription>
            </SheetHeader>

            <div class="flex flex-col gap-5 px-4">
                <div class="flex flex-col gap-2">
                    <Label for="project_id">Project</Label>
                    <NativeSelect id="project_id" v-model="form.project_id" required>
                        <NativeSelectOption value="" disabled>Select a project…</NativeSelectOption>
                        <NativeSelectOption v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.client?.name }} — {{ project.name }}
                        </NativeSelectOption>
                    </NativeSelect>
                    <p v-if="form.errors.project_id" class="text-sm text-destructive">{{ form.errors.project_id }}</p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label>Notes <span class="text-muted-foreground">(optional)</span></Label>
                    <RichTextEditor v-model="form.notes" placeholder="What are you working on?" />
                </div>

                <SheetFooter class="px-0">
                    <Button class="w-full" :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Starting…' : 'Start session' }}
                    </Button>
                </SheetFooter>
            </div>
        </SheetContent>
    </Sheet>
</template>
