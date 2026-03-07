<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { watch } from 'vue';
import type { Appearance } from '@/types';

const model = defineModel<Appearance>({ required: true });

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

function applyTheme(value: Appearance): void {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = value === 'dark' || (value === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
}

function select(value: Appearance): void {
    model.value = value;
    applyTheme(value);
}

// Sync HTML class when the saved theme is loaded from shared props
const page = usePage();
watch(
    () => page.props.theme as Appearance,
    (theme) => applyTheme(theme),
    { immediate: true },
);
</script>

<template>
    <div class="inline-flex gap-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            @click="select(value)"
            :class="[
                'flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors',
                model === value
                    ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                    : 'border-input bg-transparent text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="size-4 shrink-0" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
