<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div class="inline-flex gap-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors',
                appearance === value
                    ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                    : 'border-input bg-transparent text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="size-4 shrink-0" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
