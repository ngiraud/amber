<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { SparklesIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import InputField from '@/components/InputField.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/composables/useTranslation';
import * as reportRoutes from '@/routes/reports';
import type { ActivityReport, AiSettings } from '@/types';

const props = defineProps<{
    report: ActivityReport;
    aiSettings: AiSettings;
}>();

const open = defineModel<boolean>('open', { default: false });

const useAiSummary = ref(props.aiSettings.enabled);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle>{{ t('app.report.regenerate_title') }}</SheetTitle>
            </SheetHeader>

            <Form
                class="flex flex-col gap-5 overflow-y-auto px-4 py-2"
                :action="reportRoutes.regenerate(report)"
                #default="{ errors, processing }"
                @success="open = false"
            >
                <!-- AI toggle field -->
                <div
                    v-if="aiSettings.enabled"
                    class="flex items-center gap-3 rounded-lg border border-violet-200 bg-violet-50 px-3.5 py-3 dark:border-violet-800 dark:bg-violet-950/30"
                >
                    <SparklesIcon class="size-4 shrink-0 text-violet-500 dark:text-violet-400" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-violet-800 dark:text-violet-300">{{ t('app.report.ai_summaries') }}</p>
                        <p class="text-xs text-violet-600 dark:text-violet-400">{{ t('app.report.ai_summaries_description') }}</p>
                    </div>
                    <input type="hidden" name="use_ai_summary" :value="useAiSummary ? '1' : '0'" />
                    <Switch v-model="useAiSummary" />
                </div>

                <InputField :label="t('app.common.notes')" :error="errors.notes">
                    <Textarea name="notes" rows="4" :placeholder="t('app.common.optional') + '…'" :default-value="report.notes ?? ''" />
                </InputField>

                <SheetFooter>
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? t('app.report.regenerating') : t('app.report.regenerate_action') }}
                    </Button>
                </SheetFooter>
            </Form>
        </SheetContent>
    </Sheet>
</template>
