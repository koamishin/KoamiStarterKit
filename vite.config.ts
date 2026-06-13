import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import fastGlob from 'fast-glob';
import { defineConfig } from 'vite';

const moduleEntries = fastGlob.sync('Modules/*/resources/js/entries/*.ts', {
    cwd: __dirname,
    absolute: true,
});

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: [
                'resources/js/app.ts',
                'resources/css/filament/admin/theme.css',
                ...moduleEntries,
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        ...(command === 'serve'
            ? [
                  wayfinder({
                      formVariants: true,
                  }),
              ]
            : []),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
}));
