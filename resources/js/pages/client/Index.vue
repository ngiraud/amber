<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import ClientSheet from '@/components/ClientSheet.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
import { useOpenClientSheet } from '@/composables/useOpenClientSheet';
import { useSpotlight } from '@/composables/useSpotlight';
import { t } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import type { Client, Paginator } from '@/types';

const { spotlightClass } = useSpotlight();
const { shouldOpen } = useOpenClientSheet();

onMounted(() => {
    shouldOpen.value = false;
});

defineProps<{
    clients: Paginator<Client>;
}>();
</script>

<template>
    <AppLayout :title="t('app.client.title')">
        <template #header>
            <PageHeader :title="t('app.client.title')">
                <template #actions>
                    <ClientSheet v-model:open="shouldOpen">
                        <Button size="sm" :class="spotlightClass('new-client')">{{ t('app.client.new_client') }}</Button>
                    </ClientSheet>
                </template>
            </PageHeader>
        </template>

        <Empty v-if="clients.data.length === 0" class="mt-6">
            <EmptyTitle>{{ t('app.client.no_clients') }}</EmptyTitle>
            <EmptyDescription>{{ t('app.client.no_clients_get_started') }}</EmptyDescription>
            <ClientSheet v-model:open="shouldOpen">
                <Button size="sm" :class="spotlightClass('new-client')">{{ t('app.client.new_client') }}</Button>
            </ClientSheet>
        </Empty>

        <div v-else class="flex flex-col gap-1.5">
            <Link
                v-for="client in clients.data"
                :key="client.id"
                :href="clientRoutes.show(client)"
                class="flex items-center justify-between rounded-lg border bg-card px-5 py-4 text-card-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                <p class="text-sm font-medium">{{ client.name }}</p>

                <Badge variant="secondary">
                    {{ t('app.client.project_count', { count: client.projects_count ?? 0 }) }}
                </Badge>
            </Link>

            <div v-if="clients.last_page > 1" class="mt-4 flex items-center justify-between">
                <Button v-if="clients.prev_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="clients.prev_page_url">← {{ t('app.common.previous') }}</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">← {{ t('app.common.previous') }}</span>

                <span class="text-xs text-muted-foreground">{{
                    t('app.common.page_of', { current: clients.current_page, total: clients.last_page })
                }}</span>

                <Button v-if="clients.next_page_url" variant="ghost" size="sm" as-child>
                    <Link :href="clients.next_page_url">{{ t('app.common.next') }} →</Link>
                </Button>
                <span v-else class="text-sm text-muted-foreground/40">{{ t('app.common.next') }} →</span>
            </div>
        </div>
    </AppLayout>
</template>
