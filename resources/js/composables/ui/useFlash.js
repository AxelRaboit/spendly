import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function useFlash() {
    const page = usePage();

    watch(
        () => {
            const flashProps = page.props?.flash;
            return flashProps?.success || flashProps?.error || flashProps?.warning || flashProps?.info || null;
        },
        (message) => {
            if (!message) return;
            const flashProps = page.props?.flash;
            if (flashProps?.success) toast.success(flashProps.success);
            else if (flashProps?.error) toast.error(flashProps.error);
            else if (flashProps?.warning) toast.warning(flashProps.warning);
            else if (flashProps?.info) toast.info(flashProps.info);
        }
    );
}
