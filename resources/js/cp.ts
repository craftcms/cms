import {createApp, h} from 'vue';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import type {DefineComponent} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import '@craftcms/cp/cp.css';
import '@craftcms/cp';

import SupportWidget from '@/widgets/SupportWidget.vue';
import UpdatesWidget from '@/widgets/UpdatesWidget.vue';
import FeedWidget from '@/widgets/FeedWidget.vue';

// @ts-ignore @TODO
window.Craft = window.Craft || {};

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./Pages/**/*.vue')
    ),
  setup({el, App, props, plugin}) {
    const app = createApp({render: () => h(App, props)});

    app.component('updates-widget', UpdatesWidget);
    app.component('support-widget', SupportWidget);
    app.component('feed-widget', FeedWidget);

    app.use(plugin);
    app.mount(el);
  },
});

/**
 * Components
 */
import './components/CpGlobalSidebar.js';
