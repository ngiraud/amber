<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { t } from '@/composables/useTranslation';

defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
}>();

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <AlertDialog :open="open">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{ message }}</AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="emit('cancel')">{{ t('app.common.cancel') }}</AlertDialogCancel>
                <AlertDialogAction @click="emit('confirm')">
                    {{ confirmLabel ?? t('app.common.delete') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
