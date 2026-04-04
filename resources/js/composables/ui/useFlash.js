import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useFlash() {
    const page = usePage();
    const message = ref(null);
    const type = ref('success');

    function readFlash() {
        const flash = page.props.flash ?? {};
        if (flash.success) {
            message.value = flash.success;
            type.value = 'success';
        } else if (flash.error) {
            message.value = flash.error;
            type.value = 'error';
        } else if (flash.warning) {
            message.value = flash.warning;
            type.value = 'warning';
        } else if (flash.info) {
            message.value = flash.info;
            type.value = 'info';
        }
    }

    readFlash();

    onMounted(() => {
        const off = router.on('finish', readFlash);
        onUnmounted(off);
    });

    function dismiss() {
        message.value = null;
    }

    return { message, type, dismiss };
}
