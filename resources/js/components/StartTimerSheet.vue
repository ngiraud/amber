<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
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
                <SheetTitle>Start Session</SheetTitle>
            </SheetHeader>

            <div class="mt-6 flex flex-col gap-5 px-4">
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
                    <Label for="notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                    <Textarea id="notes" v-model="form.notes" placeholder="What are you working on?" :rows="3" />
                </div>

                <SheetFooter class="px-0">
                    <Button class="w-full" :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Starting…' : 'Start timer' }}
                    </Button>
                </SheetFooter>
            </div>
        </SheetContent>
    </Sheet>
</template>
