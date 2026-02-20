<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputField from '@/components/InputField.vue';
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
        <div class="max-w-lg">
            <div class="flex items-center gap-3">
                <Link :href="clientRoutes.index()" class="text-sm text-gray-500 hover:text-gray-700"> Clients </Link>

                <template v-if="isEditing">
                    <span class="text-gray-300">/</span>
                    <Link :href="clientRoutes.show(client!)" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ client!.name }}
                    </Link>
                </template>

                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-900">{{ isEditing ? 'Edit' : 'New client' }}</span>
            </div>

            <h1 class="mt-4 text-xl font-semibold text-gray-900">
                {{ isEditing ? 'Edit client' : 'New client' }}
            </h1>

            <Form class="mt-6 flex flex-col gap-5" :action="action" #default="{ errors, processing }">
                <InputField label="Name" :error="errors.name" required>
                    <input
                        name="name"
                        type="text"
                        :defaultValue="client?.name"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        :placeholder="isEditing ? undefined : 'Acme Corp'"
                        autofocus
                    />
                </InputField>

                <InputField label="Notes" :error="errors.notes">
                    <textarea
                        name="notes"
                        rows="3"
                        :defaultValue="client?.notes ?? undefined"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                        :placeholder="isEditing ? undefined : 'Optional notes about this client…'"
                    />
                </InputField>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            {{ processing ? (isEditing ? 'Saving…' : 'Creating…') : isEditing ? 'Save changes' : 'Create client' }}
                        </button>

                        <Link :href="isEditing ? clientRoutes.show(client!) : clientRoutes.index()" class="text-sm text-gray-500 hover:text-gray-700">
                            Cancel
                        </Link>
                    </div>

                    <button v-if="isEditing" type="button" class="text-sm text-red-600 hover:text-red-700" @click="confirmDelete = true">
                        Delete client
                    </button>
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
