import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import fastGlob from 'fast-glob';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig, lazyPlugins } from 'vite-plus';

const moduleEntries = fastGlob.sync('Modules/*/resources/js/entries/*.ts', {
    cwd: fileURLToPath(new URL('.', import.meta.url)),
    absolute: true,
});

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
                'resources/css/filament/admin/theme.css',
                ...moduleEntries,
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        inertia(),
        wayfinder({
            formVariants: true,
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
    ]),
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        ignorePatterns: [
            'composer.json',
            'resources/js/actions/**',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
    },
});
