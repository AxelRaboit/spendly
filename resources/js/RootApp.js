import { defineComponent, h } from 'vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import { useFlash } from '@/composables/ui/useFlash';

export function createRootApp(App, props) {
    return defineComponent({
        setup() {
            useFlash();
            return () => [h(App, props), h(Toaster, { theme: 'dark', position: 'bottom-center', richColors: true })];
        },
    });
}
