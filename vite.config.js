import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@css': path.resolve(__dirname, 'resources/css'),
        },
    },
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
        chunkSizeWarningLimit: 1000,
        rolldownOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('xlsx-js-style')) return 'vendor-xlsx';
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'vendor-charts';
                    if (id.includes('lucide-vue-next')) return 'vendor-icons';
                    if (id.includes('date-fns') || id.includes('marked') || id.includes('dompurify') || id.includes('driver.js')) return 'vendor-utils';
                    if (id.includes('@inertiajs') || id.includes('vue-i18n') || id.includes('vue-sonner') || id.includes('/vue/') || id.includes('/vue@')) return 'vendor-vue';
                },
            },
        },
    },
});
