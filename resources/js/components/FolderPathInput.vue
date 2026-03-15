<script setup lang="ts">
import { FolderOpen } from 'lucide-vue-next';
import { ref } from 'vue';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput } from '@/components/ui/input-group';
import { folderPicker } from '@/routes';

const props = defineProps<{
    modelValue: string;
    name: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'pick', path: string): void;
}>();

const picking = ref(false);

async function pickFolder(): Promise<void> {
    picking.value = true;

    try {
        const response = await fetch(folderPicker.url());
        const data = await response.json();

        if (data.path) {
            emit('update:modelValue', data.path);
            emit('pick', data.path);
        }
    } finally {
        picking.value = false;
    }
}
</script>

<template>
    <InputGroup>
        <InputGroupInput
            :model-value="props.modelValue"
            :name="props.name"
            type="text"
            :placeholder="props.placeholder ?? '/Users/me/code/my-folder'"
            class="font-mono"
            @update:model-value="(v) => emit('update:modelValue', String(v))"
        />
        <InputGroupAddon align="inline-end">
            <InputGroupButton type="button" size="icon-xs" :disabled="picking" @click="pickFolder">
                <FolderOpen />
            </InputGroupButton>
        </InputGroupAddon>
    </InputGroup>
</template>
