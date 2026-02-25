<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/routes/clients';
import type { Client } from '@/types';

const props = defineProps<{
    client?: Client;
}>();

const isEditing = computed(() => !!props.client);
const action = computed(() => (isEditing.value ? clientRoutes.update(props.client!) : clientRoutes.store()));

const confirmDelete = ref(false);
</script>

<template>
    <AppLayout :title="isEditing ? `Edit — ${client!.name}` : 'New client'">
        <template #header>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link :href="clientRoutes.index()">Clients</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>

                    <template v-if="isEditing">
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink as-child>
                                <Link :href="clientRoutes.show(client!)">{{ client!.name }}</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                    </template>

                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>{{ isEditing ? 'Edit' : 'New client' }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <h1 class="mt-2 text-xl font-semibold">
                {{ isEditing ? 'Edit client' : 'New client' }}
            </h1>
        </template>

        <div class="max-w-lg">
            <Form class="flex flex-col gap-5" :action="action" #default="{ errors, processing }">
                <InputField label="Name" :error="errors.name" required>
                    <Input name="name" type="text" :default-value="client?.name" :placeholder="isEditing ? undefined : 'Acme Corp'" autofocus />
                </InputField>

                <InputField label="Notes" :error="errors.notes">
                    <Textarea
                        name="notes"
                        rows="3"
                        :default-value="client?.notes ?? undefined"
                        :placeholder="isEditing ? undefined : 'Optional notes about this client…'"
                    />
                </InputField>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create client' }}
                        </Button>

                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="isEditing ? clientRoutes.show(client!) : clientRoutes.index()">Cancel</Link>
                        </Button>
                    </div>

                    <Button
                        v-if="isEditing"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        type="button"
                        @click="confirmDelete = true"
                    >
                        Delete client
                    </Button>
                </div>
            </Form>
        </div>

        <Form v-if="isEditing" :action="clientRoutes.destroy(client!)" #default="{ submit }">
            <ConfirmDialog
                :open="confirmDelete"
                title="Delete client"
                :message="`Are you sure you want to delete ${client!.name}? All associated projects will be deleted.`"
                @confirm="submit"
                @cancel="confirmDelete = false"
            />
        </Form>
    </AppLayout>
</template>
