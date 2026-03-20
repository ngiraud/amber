<script setup lang="ts">
import type { DateValue } from 'reka-ui'
import { parseDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useDateFormat } from '@/composables/useDateFormat'

const props = defineProps<{
    modelValue?: string
    min?: string
    max?: string
    placeholder?: string
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const { formatDate } = useDateFormat()

const dateValue = computed<DateValue | undefined>(() => {
    return props.modelValue ? parseDate(props.modelValue) : undefined
})

const minValue = computed<DateValue | undefined>(() => {
    return props.min ? parseDate(props.min) : undefined
})

const maxValue = computed<DateValue | undefined>(() => {
    return props.max ? parseDate(props.max) : undefined
})

const displayValue = computed(() => {
    if (!props.modelValue) {
        return props.placeholder ?? 'Pick a date'
    }

    return formatDate(`${props.modelValue}T00:00:00`)
})

function onSelect(value: DateValue | undefined): void {
    if (value) {
        emit('update:modelValue', value.toString())
        open.value = false
    }
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                class="w-full justify-start text-left font-normal dark:bg-input/30 dark:hover:bg-input/50"
                :class="{ 'text-muted-foreground': !modelValue }"
            >
                <CalendarIcon class="mr-2 size-4 shrink-0" />
                {{ displayValue }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                :model-value="dateValue"
                :min-value="minValue"
                :max-value="maxValue"
                layout="month-and-year"
                @update:model-value="onSelect"
            />
        </PopoverContent>
    </Popover>
</template>
