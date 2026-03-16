<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as sessionRoutes from '@/routes/sessions';

const page = usePage();
const projects = computed(() => page.props.projects ?? []);

const props = defineProps<{ open?: boolean; date?: string }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const open = ref(props.open ?? false);

watch(
    () => props.open,
    (val) => {
        if (val !== undefined) {
            open.value = val;
        }
    },
);

watch(open, (val) => {
    emit('update:open', val);

    if (val) {
        router.reload({ only: ['projects'] });
    }
});

const form = useForm({
    project_id: '',
    started_at: props.date ? `${props.date}T09:00` : '',
    ended_at: props.date ? `${props.date}T10:00` : '',
    description: '',
    notes: '',
});

function submit(): void {
    form.submit(sessionRoutes.store(), {
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
                <SheetTitle>Log Past Session</SheetTitle>
            </SheetHeader>

            <div class="mt-6 flex flex-col gap-5 px-4">
                <div class="flex flex-col gap-2">
                    <Label for="past-project_id">Project</Label>
                    <NativeSelect id="past-project_id" v-model="form.project_id" required>
                        <NativeSelectOption value="" disabled>Select a project…</NativeSelectOption>
                        <NativeSelectOption v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.client?.name }} — {{ project.name }}
                        </NativeSelectOption>
                    </NativeSelect>
                    <p v-if="form.errors.project_id" class="text-sm text-destructive">{{ form.errors.project_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <InputField label="Start" :error="form.errors.started_at">
                        <Input id="past-started_at" v-model="form.started_at" type="datetime-local" class="dark:[color-scheme:dark]" required />
                    </InputField>

                    <InputField label="End" :error="form.errors.ended_at">
                        <Input id="past-ended_at" v-model="form.ended_at" type="datetime-local" class="dark:[color-scheme:dark]" required />
                    </InputField>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="past-description">Description <span class="text-muted-foreground">(optional)</span></Label>
                    <Input id="past-description" v-model="form.description" type="text" placeholder="e.g. Meeting with client" />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="past-notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                    <Textarea id="past-notes" v-model="form.notes" placeholder="Additional notes…" :rows="2" />
                </div>

                <SheetFooter class="px-0">
                    <Button class="w-full" :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Saving…' : 'Add session' }}
                    </Button>
                </SheetFooter>
            </div>
        </SheetContent>
    </Sheet>
</template>
