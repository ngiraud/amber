<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClockIcon, SettingsIcon, UsersIcon } from 'lucide-vue-next';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import * as clientRoutes from '@/routes/clients';
import * as sessionRoutes from '@/routes/sessions';
import * as settingsRoutes from '@/routes/settings';

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <Sidebar variant="inset">
        <SidebarHeader class="px-4 py-3">
            <span class="text-sm font-semibold tracking-wide">CRA Tracker</span>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrl(clientRoutes.index())">
                                <Link :href="clientRoutes.index()">
                                    <UsersIcon />
                                    <span>Clients</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrl(sessionRoutes.index())">
                                <Link :href="sessionRoutes.index()">
                                    <ClockIcon />
                                    <span>Sessions</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrl(settingsRoutes.edit())">
                        <Link :href="settingsRoutes.edit().url">
                            <SettingsIcon />
                            <span>Settings</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>
