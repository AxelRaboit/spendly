import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@inertiajs/vue3', 'vue-i18n', 'vue-sonner'],
                    'vendor-charts': ['chart.js', 'vue-chartjs'],
                    'vendor-xlsx': ['xlsx-js-style'],
                    'vendor-utils': ['date-fns', 'marked', 'dompurify', 'driver.js'],
                    'vendor-icons': ['lucide-vue-next'],
                },
            },
        },
    },
});
