import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function useFlash() {
    const page = usePage();

    watch(
        () => {
            const f = page.props?.flash;
            return f?.success || f?.error || f?.warning || f?.info || null;
        },
        (message) => {
            if (!message) return;
            const f = page.props?.flash;
            if (f?.success) toast.success(f.success);
            else if (f?.error) toast.error(f.error);
            else if (f?.warning) toast.warning(f.warning);
            else if (f?.info) toast.info(f.info);
        }
    );
}
