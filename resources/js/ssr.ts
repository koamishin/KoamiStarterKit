import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

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

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: resolvePage,
            setup: ({ App, props, plugin }) =>
                createSSRApp({ render: () => h(App, props) }).use(plugin),
        }),
    { cluster: true },
);
