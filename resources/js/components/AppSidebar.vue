<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ActivityIcon, CalendarDaysIcon, ClockIcon, FileTextIcon, FolderIcon, LayoutDashboardIcon, SettingsIcon, UsersIcon } from 'lucide-vue-next';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { home } from '@/routes';
import * as activityRoutes from '@/routes/activity';
import * as clientRoutes from '@/routes/clients';
import * as projectRoutes from '@/routes/projects';
import * as reportRoutes from '@/routes/reports';
import * as sessionRoutes from '@/routes/sessions';
import * as settingsRoutes from '@/routes/settings';
import * as timelineRoutes from '@/routes/timeline';

const { isCurrentUrl, isCurrentUrlOrChild } = useCurrentUrl();
</script>

<template>
    <Sidebar variant="inset" collapsible="icon" class="pt-11">
        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrl(home())" tooltip="Dashboard">
                                <Link :href="home().url" class="items-center justify-center">
                                    <LayoutDashboardIcon />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(timelineRoutes.index())" tooltip="Timeline">
                                <Link :href="timelineRoutes.index().url" class="items-center justify-center">
                                    <CalendarDaysIcon />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(clientRoutes.index())" tooltip="Clients">
                                <Link :href="clientRoutes.index()" class="items-center justify-center">
                                    <UsersIcon />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(projectRoutes.index())" tooltip="Projects">
                                <Link :href="projectRoutes.index()" class="items-center justify-center">
                                    <FolderIcon />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(reportRoutes.index())" tooltip="Reports">
                                <Link :href="reportRoutes.index()" class="items-center justify-center">
                                    <FileTextIcon />
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
                    <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(sessionRoutes.index())" tooltip="Sessions">
                        <Link :href="sessionRoutes.index()" class="items-center justify-center">
                            <ClockIcon />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrl(activityRoutes.index())" tooltip="Activity">
                        <Link :href="activityRoutes.index()" class="items-center justify-center">
                            <ActivityIcon />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <ThemeSwitcher />
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child :is-active="isCurrentUrlOrChild(settingsRoutes.index())" tooltip="Settings">
                        <Link :href="settingsRoutes.index()" class="items-center justify-center">
                            <SettingsIcon />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>
