import '@css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createRootApp } from '@/RootApp';
import components from '@/plugins/components';
import { createI18nInstance } from '@/i18n/index';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const locale = props.initialPage.props.locale ?? 'fr';
        const i18n = createI18nInstance(locale);

        return createApp(createRootApp(App, props)).use(plugin).use(ZiggyVue).use(components).use(i18n).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
