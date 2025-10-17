import {createApp, h} from 'vue';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import type {DefineComponent} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import '@craftcms/cp/cp.css';
import '@craftcms/cp';

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue')
    ),
  setup({el, App, props, plugin}) {
    createApp({render: () => h(App, props)})
      .use(plugin)
      .mount(el);
  },
});
