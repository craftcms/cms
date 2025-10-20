import {createApp, h} from 'vue';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import type {DefineComponent} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import '@craftcms/cp/cp.css';
import '@craftcms/cp';

import Updates from '@/widgets/UpdatesWidget.vue';

// @ts-ignore @TODO
window.Craft = window.Craft || {};

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue')
    ),
  setup({el, App, props, plugin}) {
    const app = createApp({render: () => h(App, props)});

    app.component('updates-widget', Updates);

    app.use(plugin);
    app.mount(el);
  },
});
