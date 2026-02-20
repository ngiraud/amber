<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';
import { toast } from 'vue-sonner';

const off = router.on('flash', (event) => {
    const { success, error } = event.detail.flash;
    if (success) {
        toast.success(success);
    } else if (error) {
        toast.error(error);
    }
});

onUnmounted(() => {
    off();
});
</script>
