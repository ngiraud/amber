<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { HomeIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const title = computed(() => {
    return (
        {
            403: 'Access Denied',
            404: 'Page Not Found',
            500: 'Server Error',
            503: 'Service Unavailable',
        }[props.status] ?? 'An Error Occurred'
    );
});

const description = computed(() => {
    return (
        {
            403: "You don't have permission to access this page.",
            404: "The page you're looking for doesn't exist or has been moved.",
            500: 'Something went wrong on our end. Please try again.',
            503: 'The application is temporarily unavailable. Please try again shortly.',
        }[props.status] ?? 'An unexpected error occurred.'
    );
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-background p-8 text-foreground">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="text-7xl font-black text-muted-foreground/20 tabular-nums">{{ status }}</span>
            <h1 class="text-xl font-semibold">{{ title }}</h1>
            <p class="max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        </div>

        <Button variant="outline" size="sm" as-child>
            <Link :href="home().url">
                <HomeIcon class="mr-1.5 size-3.5" />
                Back to Dashboard
            </Link>
        </Button>
    </div>
</template>
