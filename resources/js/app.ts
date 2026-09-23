import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeColorTheme } from './composables/useColorTheme';
import { initializeTheme } from './composables/useAppearance';
import { initializeFlashToast } from './lib/flashToast';

import { resolveLayout } from './layouts/resolveLayout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const appPages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
const modulePages = import.meta.glob<DefineComponent>(
    '../../Modules/*/resources/js/pages/**/*.vue',
);
const pages = { ...appPages, ...modulePages };

const resolvePage = (name: string) => {
    const appPath = `./pages/${name}.vue`;
    const modulePath = Object.keys(modulePages).find((path) =>
        path.endsWith(`/resources/js/pages/${name}.vue`),
    );

    return resolvePageComponent(modulePath ?? appPath, pages);
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    layout: resolveLayout,
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
initializeColorTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
