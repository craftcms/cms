import {ConfigService} from '@craftcms/cp/services/Config.ts.mjs';
import {QueueService} from '@craftcms/cp/services/Queue.ts.mjs';
import {createInertiaApp, router} from '@inertiajs/vue3';
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
import DeprecationErrorsToolbar from '@/components/utilities/DeprecationErrors/DeprecationErrorsToolbar.vue';
import {setTranslations} from '@craftcms/cp/utilities/translate.ts.mjs';
import TotpForm from '@/components/Auth/TotpForm.vue';
import RecoveryCodesForm from '@/components/Auth/RecoveryCodesForm.vue';

let bootedCallbacks: Array<(instance: any) => void> = [];
let bootingCallbacks: Array<(instance: any) => void> = [];

// Instantiate services
const config = ConfigService.getInstance();
const queue = QueueService.getInstance();

// Create our object
const Cp = {
  initialConfig: {} as Record<string, any>,

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
      runAutomatically: config.get('runQueueAutomatically', true),
      enabled: true,
      appId: config.get('systemUid', ''),
      canAccessQueueManager: config.get('canAccessQueueManager', false),
    });

    setTranslations(this.initialConfig.translations);
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
      pages: '../pages',
      withApp(app) {
        app.provide(Queue, queue);
        app.provide(Axios, axios);
        app.provide(Config, config);

        app.component('QueueManager', QueueManager);
        app.component('QueueManagerToolbar', QueueManagerToolbar);
        app.component('DeprecationErrors', DeprecationErrors);
        app.component('DeprecationErrorsToolbar', DeprecationErrorsToolbar);
        app.component('ClearCaches', ClearCaches);
        app.component('FindReplace', FindReplace);
        app.component('DatabaseBackup', DatabaseBackup);
        app.component('Migrations', Migrations);
        app.component('Updates', Updates);
        app.component('ProjectConfig', ProjectConfig);
        app.component('AssetIndexes', AssetIndexes);
        app.component('SystemMessages', SystemMessages);

        app.component('TotpForm', TotpForm);
        app.component('RecoveryCodesForm', RecoveryCodesForm);
      },
    });

    handleNonInertiaRequests();

    console.log('Calling booted callbacks', bootedCallbacks);
    bootedCallbacks.forEach((callback) => callback(this));
    bootedCallbacks = [];
  },
};

function handleNonInertiaRequests() {
  let fallbackUrl = '';

  router.on('start', (event) => {
    const visit = event.detail.visit;

    if (visit.prefetch || visit.async || visit.method !== 'get') {
      return;
    }

    fallbackUrl = visit.url.href;
  });

  router.on('finish', (event) => {
    const visit = event.detail.visit;

    if (fallbackUrl === visit.url.href) {
      fallbackUrl = '';
    }
  });

  router.on('httpException', (event) => {
    const response = event.detail.response;

    const shouldReload =
      [200, 302, 301].includes(response.status) &&
      response.headers['content-type']?.includes('text/html');

    if (response.headers['x-redirect']) {
      fallbackUrl = response.headers['x-redirect'];
    }

    if (!fallbackUrl || !shouldReload) {
      return;
    }

    event.preventDefault();
    window.location.assign(fallbackUrl);
  });
}

export default Cp;
