<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import * as clientRoutes from '@/routes/clients';
import * as settingsRoutes from '@/routes/settings';

const page = usePage();

function isActive(prefix: string): boolean {
    return page.url.startsWith(prefix);
}
</script>

<template>
    <aside class="flex h-full w-56 shrink-0 flex-col bg-gray-900">
        <div class="flex h-14 items-center px-5">
            <span class="text-sm font-semibold tracking-wide text-white">CRA Tracker</span>
        </div>

        <nav class="flex flex-1 flex-col gap-0.5 px-3 py-2">
            <Link
                :href="clientRoutes.index().url"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors"
                :class="
                    isActive('/clients')
                        ? 'bg-white/10 text-white'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white'
                "
            >
                Clients
            </Link>
        </nav>

        <div class="border-t border-white/10 px-3 py-3">
            <Link
                :href="settingsRoutes.edit().url"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors"
                :class="
                    isActive('/settings')
                        ? 'bg-white/10 text-white'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white'
                "
            >
                Settings
            </Link>
        </div>
    </aside>
</template>
