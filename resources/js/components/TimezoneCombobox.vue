<script setup lang="ts">
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const props = defineProps<{
    timezones: string[];
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);

const groupedTimezones = computed(() => {
    const groups = new Map<string, string[]>();

    for (const tz of props.timezones) {
        const region = tz.includes('/') ? tz.split('/')[0] : tz;

        if (!groups.has(region)) {
            groups.set(region, []);
        }

        groups.get(region)!.push(tz);
    }

    return groups;
});

const displayLabel = computed(() => {
    if (!props.modelValue) {
        return 'Select a timezone…';
    }

    return props.modelValue.includes('/') ? props.modelValue.split('/').slice(1).join(' / ').replaceAll('_', ' ') : props.modelValue;
});

function selectTimezone(tz: string): void {
    emit('update:modelValue', tz);
    open.value = false;
}

function formatCity(tz: string): string {
    return tz.includes('/') ? tz.split('/').slice(1).join(' / ').replaceAll('_', ' ') : tz;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button variant="outline" role="combobox" :aria-expanded="open" class="h-9 w-full justify-between px-3 font-normal">
                <span class="truncate text-sm">{{ displayLabel }}</span>
                <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="p-0" align="start" :style="{ width: 'var(--reka-popper-anchor-width)' }">
            <Command>
                <CommandInput placeholder="Search timezone…" />
                <CommandList class="max-h-60">
                    <CommandEmpty>No timezone found.</CommandEmpty>
                    <CommandGroup v-for="[region, zones] in groupedTimezones" :key="region" :heading="region">
                        <CommandItem v-for="tz in zones" :key="tz" :value="tz" @select="selectTimezone(tz)">
                            <Check class="size-4 shrink-0" :class="modelValue === tz ? 'opacity-100' : 'opacity-0'" />
                            {{ formatCity(tz) }}
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
