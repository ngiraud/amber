<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { SparklesIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import * as reportRoutes from '@/routes/reports';
import type { AiSettings, Client } from '@/types';

const props = defineProps<{
    clients: Client[];
    aiSettings: AiSettings;
}>();

const useAiSummary = ref(props.aiSettings.enabled);

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

        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Generate Activity Report</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 overflow-y-auto px-4 py-2"
                :action="reportRoutes.store()"
                #default="{ errors, processing }"
                @success="() => (open = false)"
            >
                <!-- AI toggle field -->
                <div
                    v-if="aiSettings.enabled"
                    class="flex items-center gap-3 rounded-lg border border-violet-200 bg-violet-50 px-3.5 py-3 dark:border-violet-800 dark:bg-violet-950/30"
                >
                    <SparklesIcon class="size-4 shrink-0 text-violet-500 dark:text-violet-400" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-violet-800 dark:text-violet-300">AI summaries</p>
                        <p class="text-xs text-violet-600 dark:text-violet-400">Summarize each activity line with your LLM.</p>
                    </div>
                    <input type="hidden" name="use_ai_summary" :value="useAiSummary ? '1' : '0'" />
                    <Switch v-model="useAiSummary" />
                </div>

                <InputField label="Client" :error="errors.client_id" required>
                    <NativeSelect name="client_id">
                        <NativeSelectOption value="" disabled selected>Select a client…</NativeSelectOption>
                        <NativeSelectOption v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </NativeSelectOption>
                    </NativeSelect>
                </InputField>

                <div class="grid grid-cols-2 gap-4">
                    <InputField label="Month" :error="errors.month" required>
                        <NativeSelect name="month" :model-value="new Date().getMonth() + 1">
                            <NativeSelectOption v-for="m in MONTHS" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                    </InputField>

                    <InputField label="Year" :error="errors.year" required>
                        <NativeSelect name="year" :model-value="currentYear">
                            <NativeSelectOption v-for="y in YEARS" :key="y" :value="y">
                                {{ y }}
                            </NativeSelectOption>
                        </NativeSelect>
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
