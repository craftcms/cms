import {QueueService, ConfigService} from '@craftcms/cp';
import {createInertiaApp, router} from '@inertiajs/vue3';
import QueueManager from '@/modules/utilities/components/queue-manager/QueueManager.vue';
import {Axios, Config, Queue} from '@/common/types/keys';
import axios from 'axios';
import QueueManagerToolbar from '@/modules/utilities/components/queue-manager/QueueManagerToolbar.vue';
import DeprecationErrors from '@/modules/utilities/components/deprecation-errors/DeprecationErrors.vue';
import ClearCaches from '@/modules/utilities/components/clear-caches/ClearCaches.vue';
import FindReplace from '@/modules/utilities/components/find-replace/FindReplace.vue';
import DatabaseBackup from '@/modules/utilities/components/DatabaseBackup.vue';
import Migrations from '@/modules/utilities/components/Migrations.vue';
import Updates from '@/modules/updater/components/Updates.vue';
import ProjectConfig from '@/modules/utilities/components/project-config/ProjectConfig.vue';
import AssetIndexes from '@/modules/utilities/components/asset-indexes/AssetIndexes.vue';
import SystemMessages from '@/modules/utilities/components/system-messages/SystemMessages.vue';
import DeprecationErrorsToolbar from '@/modules/utilities/components/deprecation-errors/DeprecationErrorsToolbar.vue';
import {setTranslations} from '@craftcms/cp/utilities/translate';
import {setUrlDefaults} from '@/wayfinder';
import {inertiaPageRegistry, resolveInertiaPage} from './inertia-pages.js';
import AppLayout from '@/common/layouts/AppLayout.vue';
import {createCpComponentRegistry} from './components.js';
import {configureIcons} from './icons.js';
import LocalFsSettings from '@/components/Filesystems/LocalFsSettings.vue';

let bootedCallbacks: Array<(instance: any) => void> = [];
let bootingCallbacks: Array<(instance: any) => void> = [];

/**
 * Pages under these prefixes render outside the CP shell: auth screens wrap
 * `<AuthBase/>` themselves and the installer is a standalone wizard.
 */
const shellLessPagePrefixes = ['auth/', 'install/'];

/**
 * The default Inertia layout. Pages that render `<AppLayout>` inline (to pass
 * it props or fill its slots) opt out with `defineOptions({layout: []})`.
 */
function defaultPageLayout(name: string) {
  if (shellLessPagePrefixes.some((prefix) => name.startsWith(prefix))) {
    return null;
  }

  return AppLayout;
}

// Instantiate services
const config = ConfigService.getInstance();
const queue = QueueService.getInstance();
const components = createCpComponentRegistry();

function routeSegment(value: unknown): string {
  if (value === null || value === undefined) {
    return '';
  }

  return value.toString().replace(/^\/+|\/+$/g, '');
}

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

  get $inertia() {
    return inertiaPageRegistry;
  },

  get $components() {
    return components;
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
    configureIcons(config.get('iconBaseUrl', '/vendor/craft/icons'));

    setUrlDefaults(() => ({
      cpTrigger: routeSegment(config.get('cpTrigger')),
      actionTrigger: routeSegment(config.get('actionTrigger')),
    }));

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
      this.$config.get('csrfTokenValue');

    console.groupCollapsed('Craft configuration');
    console.log(config.all().entries());
    console.groupEnd();

    console.log('Calling booting callbacks', bootingCallbacks);
    bootingCallbacks.forEach((callback) => callback(this));
    bootingCallbacks = [];

    await createInertiaApp({
      resolve: (name) => resolveInertiaPage(name),
      layout: defaultPageLayout,
      title: (title) => `${title} - ${this.$config.get('systemName')}`,
      withApp(app) {
        app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');

        app.provide(Queue, queue);
        app.provide(Axios, axios);
        app.provide(Config, config);
        app.provide(Craft, config);

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
        app.component('LocalFsSettings', LocalFsSettings);

        components.install(app);
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
