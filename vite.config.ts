import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { glob } from 'glob';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';

// Get all Vue components in the pages directory
const pages = glob.sync('resources/js/pages/**/*.vue').map((file) => file);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', ...pages],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                app: 'resources/js/app.ts',
            },
        },
    },
});
