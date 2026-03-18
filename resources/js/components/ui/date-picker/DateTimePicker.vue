<script setup lang="ts">
import type { DateValue } from 'reka-ui'
import { parseDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useDateFormat } from '@/composables/useDateFormat'

const props = defineProps<{
    modelValue?: string
    placeholder?: string
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const { formatDate, formatTime } = useDateFormat()

const datePart = computed(() => props.modelValue?.slice(0, 10) ?? '')
const timePart = computed(() => props.modelValue?.slice(11) || '09:00')

const hours = computed(() => timePart.value.split(':')[0] ?? '09')
const minutes = computed(() => timePart.value.split(':')[1] ?? '00')

const dateValue = computed<DateValue | undefined>(() => {
    return datePart.value ? parseDate(datePart.value) : undefined
})

const displayValue = computed(() => {
    if (!props.modelValue) {
        return props.placeholder ?? 'Pick a date & time'
    }

    return `${formatDate(props.modelValue)} ${formatTime(props.modelValue)}`
})

const hourOptions = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'))
const minuteOptions = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'))

function onDateSelect(value: DateValue | undefined): void {
    if (value) {
        emit('update:modelValue', `${value.toString()}T${timePart.value}`)
    }
}

function onHourChange(event: Event): void {
    const h = (event.target as HTMLSelectElement).value
    const date = datePart.value || new Date().toISOString().slice(0, 10)
    emit('update:modelValue', `${date}T${h}:${minutes.value}`)
}

function onMinuteChange(event: Event): void {
    const m = (event.target as HTMLSelectElement).value
    const date = datePart.value || new Date().toISOString().slice(0, 10)
    emit('update:modelValue', `${date}T${hours.value}:${m}`)
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
                layout="month-and-year"
                @update:model-value="onDateSelect"
            />
            <div class="flex items-center justify-center gap-1.5 border-t px-4 py-3">
                <NativeSelect
                    class="w-20 text-center"
                    :model-value="hours"
                    @change="onHourChange"
                >
                    <NativeSelectOption v-for="h in hourOptions" :key="h" :value="h">{{ h }}</NativeSelectOption>
                </NativeSelect>
                <span class="text-muted-foreground font-medium">:</span>
                <NativeSelect
                    class="w-20 text-center"
                    :model-value="minutes"
                    @change="onMinuteChange"
                >
                    <NativeSelectOption v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</NativeSelectOption>
                </NativeSelect>
            </div>
        </PopoverContent>
    </Popover>
</template>
