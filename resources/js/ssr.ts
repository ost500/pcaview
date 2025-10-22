import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, DefineComponent, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { ZiggyVue } from 'ziggy-js';
import type { ZiggyConfig } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
            setup: ({ App, props, plugin }) => {
                // SSR에서 Ziggy 설정 안전하게 초기화
                const ziggy: ZiggyConfig = (page.props.ziggy as ZiggyConfig) || {
                    routes: {},
                    location: '',
                    url: 'http://localhost',
                    port: null,
                    defaults: {},
                };

                // location이 빈 문자열이면 기본값 설정
                const location = ziggy.location || ziggy.url || 'http://localhost';

                return createSSRApp({ render: () => h(App, props) })
                    .use(plugin)
                    .use(ZiggyVue, {
                        ...ziggy,
                        location: new URL(location),
                    });
            },
        }),
    { cluster: true },
);
