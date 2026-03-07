<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import * as reportRoutes from '@/routes/reports';
import type { Client } from '@/types';

defineProps<{
    clients: Client[];
}>();

const currentYear = new Date().getFullYear();

const MONTHS = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const YEARS = Array.from({ length: 5 }, (_, i) => currentYear - i);

const open = ref(false);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>

        <SheetContent side="right" class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Generate Activity Report</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 overflow-y-auto px-4 py-2"
                :action="reportRoutes.store()"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <InputField label="Client" :error="errors.client_id" required>
                    <select
                        name="client_id"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled selected>Select a client…</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select>
                </InputField>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Month" :error="errors.month" required>
                        <select
                            name="month"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option v-for="m in MONTHS" :key="m.value" :value="m.value" :selected="m.value === new Date().getMonth() + 1">
                                {{ m.label }}
                            </option>
                        </select>
                    </InputField>

                    <InputField label="Year" :error="errors.year" required>
                        <select
                            name="year"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option v-for="y in YEARS" :key="y" :value="y" :selected="y === currentYear">
                                {{ y }}
                            </option>
                        </select>
                    </InputField>
                </div>

                <InputField label="Notes" :error="errors.notes">
                    <Textarea name="notes" rows="3" placeholder="Optional notes…" />
                </InputField>

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? 'Generating…' : 'Generate report' }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
