import {ConfigService} from '@craftcms/cp/dist/services/Config.ts.mjs';
import {QueueService} from '@craftcms/cp/dist/services/Queue.ts.mjs';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, type DefineComponent, h} from 'vue';
import QueueManager from '@/components/utilities/QueueManager/QueueManager.vue';
import {Axios, Config, Queue} from '@/types/keys';
import axios from 'axios';
import QueueManagerToolbar from '@/components/utilities/QueueManager/QueueManagerToolbar.vue';
import DeprecationErrors from '@/components/utilities/DeprecationErrors/DeprecationErrors.vue';
import ClearCaches from '@/components/utilities/ClearCaches/ClearCaches.vue';
import FindReplace from '@/components/utilities/FindReplace/FindReplace.vue';
import DatabaseBackup from '@/components/utilities/DatabaseBackup.vue';
import Migrations from '@/components/utilities/Migrations.vue';
import Updates from '@/components/utilities/Updates/Updates.vue';
import ProjectConfig from '@/components/utilities/ProjectConfig/ProjectConfig.vue';
import AssetIndexes from '@/components/utilities/AssetIndexes/AssetIndexes.vue';
import SystemMessages from '@/components/utilities/SystemMessages/SystemMessages.vue';

let bootedCallbacks: Array<(instance: any) => void> = [];
let bootingCallbacks: Array<(instance: any) => void> = [];

// Instantiate services
const config = ConfigService.getInstance();
const queue = QueueService.getInstance();

// Create our object
const Cp = {
  initialConfig: {},

  get $config() {
    return config;
  },

  get $queue() {
    return queue;
  },

  get $axios() {
    return axios;
  },

  booted(callback: (instance: any) => void) {
    bootedCallbacks.push(callback);
  },

  booting(callback: (instance: any) => void) {
    bootingCallbacks.push(callback);
  },

  config(config: Record<any, any>) {
    this.initialConfig = config;
  },

  init() {
    config.initialize(this.initialConfig);
    queue.initialize({
      enabled: true,
      appId: config.get('systemUid', ''),
      canAccessQueueManager: config.get('canAccessQueueManager', false),
    });
  },

  async start() {
    this.init();

    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['X-CSRF-TOKEN'] =
      this.$config.get('csrfToken');

    console.groupCollapsed('Craft configuration');
    console.log(config.all().entries());
    console.groupEnd();

    console.log('Calling booting callbacks', bootingCallbacks);
    bootingCallbacks.forEach((callback) => callback(this));
    bootingCallbacks = [];

    await createInertiaApp({
      resolve: (name) =>
        resolvePageComponent(
          `../pages/${name}.vue`,
          import.meta.glob<DefineComponent>('../pages/**/*.vue')
        ),
      setup({el, App, props, plugin}) {
        const app = createApp({render: () => h(App, props)}).use(plugin);

        app.provide(Queue, queue);
        app.provide(Axios, axios);
        app.provide(Config, config);

        app.component('QueueManager', QueueManager);
        app.component('QueueManagerToolbar', QueueManagerToolbar);
        app.component('DeprecationErrors', DeprecationErrors);
        app.component('ClearCaches', ClearCaches);
        app.component('FindReplace', FindReplace);
        app.component('DatabaseBackup', DatabaseBackup);
        app.component('Migrations', Migrations);
        app.component('Updates', Updates);
        app.component('ProjectConfig', ProjectConfig);
        app.component('AssetIndexes', AssetIndexes);
        app.component('SystemMessages', SystemMessages);

        app.mount(el);
      },
    });

    console.log('Calling booted callbacks', bootedCallbacks);
    bootedCallbacks.forEach((callback) => callback(this));
    bootedCallbacks = [];
  },
};

export default Cp;
