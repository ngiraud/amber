<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { applyTheme } from '@/composables/useAppearance';
import { t } from '@/composables/useTranslation';
import * as generalRoutes from '@/routes/settings/general';
import type { Appearance, GeneralSettings } from '@/types';

const page = usePage();

const open = ref(false);

const currentTheme = computed(() => (page.props.generalSettings as GeneralSettings)?.theme || 'system');

const themes = computed(() => [
    { value: 'light', Icon: Sun, label: t('app.settings.theme.light') },
    { value: 'dark', Icon: Moon, label: t('app.settings.theme.dark') },
    { value: 'system', Icon: Monitor, label: t('app.settings.theme.system') },
]);

const currentIcon = computed(() => themes.value.find((theme) => theme.value === currentTheme.value)?.Icon ?? Monitor);

function select(theme: Appearance): void {
    open.value = false;

    if (theme === currentTheme.value) {
        return;
    }

    applyTheme(theme);

    const settings = page.props.generalSettings as GeneralSettings;

    router.put(
        generalRoutes.update(),
        {
            ...settings,
            theme,
        },
        {
            preserveScroll: true,
            preserveState: true,
            showProgress: false,
        },
    );
}
</script>

<template>
    <SidebarMenuButton size="lg" class="cursor-pointer items-center justify-center" :tooltip="t('app.settings.fields.theme')">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <component :is="currentIcon" />
                <span class="sr-only">{{ t('app.settings.fields.theme') }}</span>
            </PopoverTrigger>
            <PopoverContent side="right" :side-offset="24" align="center" class="w-auto p-1">
                <div class="flex items-center gap-0.5">
                    <button
                        v-for="{ value, Icon, label } in themes"
                        :key="value"
                        type="button"
                        @click="select(value)"
                        :class="[
                            'flex size-8 cursor-pointer items-center justify-center rounded-md transition-colors',
                            currentTheme === value
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent/80 hover:text-accent-foreground',
                        ]"
                    >
                        <component :is="Icon" class="size-4" />
                        <span class="sr-only">{{ label }}</span>
                    </button>
                </div>
            </PopoverContent>
        </Popover>
    </SidebarMenuButton>
</template>
