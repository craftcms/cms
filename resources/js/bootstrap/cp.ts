import {QueueService} from '@/common/services/Queue';
import {createInertiaApp, router} from '@inertiajs/vue3';
import QueueManager from '@/modules/utilities/components/queue-manager/QueueManager.vue';
import {Axios, Queue} from '@/common/types/keys';
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
import CpLink from '@/common/components/CpLink.vue';
import {setTranslations} from '@craftcms/ui/utilities/translate';
import {configureIcons} from './icons.js';
import {setUrlDefaults} from '@/wayfinder';
import {inertiaPageRegistry, resolveInertiaPage} from './inertia-pages.js';
import AppLayout from '@/common/layouts/AppLayout.vue';
import {createCpComponentRegistry} from './components.js';

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
const queue = QueueService.getInstance();
const components = createCpComponentRegistry();
let hasBooted = false;

function routeSegment(value: unknown): string {
  if (value === null || value === undefined) {
    return '';
  }

  return value.toString().replace(/^\/+|\/+$/g, '');
}

function booting(callback: (craft: any) => void) {
  (window.bootingCallbacks ||= []).push(callback);
}

function booted(callback: (craft: any) => void) {
  if (hasBooted) {
    callback(window.Craft);
  } else {
    (window.bootedCallbacks ||= []).push(callback);
  }
}

function init() {
  setUrlDefaults(() => ({
    cpTrigger: routeSegment(window.Craft.cpTrigger),
    actionTrigger: routeSegment(window.Craft.actionTrigger),
  }));

  queue.initialize({
    runAutomatically: window.Craft.runQueueAutomatically ?? true,
    enabled: true,
    appId: window.Craft.systemUid ?? '',
    canAccessQueueManager: window.Craft.canAccessQueueManager ?? false,
  });

  setTranslations(window.Craft.translations ?? {});
  configureIcons(window.Craft.iconBaseUrl ?? '/vendor/craft/icons');
}

async function start() {
  init();

  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  axios.defaults.headers.common['X-CSRF-TOKEN'] =
    window.Craft.csrfTokenValue;

  (window.bootingCallbacks ?? []).forEach((callback) =>
    callback(window.Craft)
  );
  window.bootingCallbacks = [];

  await createInertiaApp({
    resolve: (name) => resolveInertiaPage(name),
    layout: defaultPageLayout,
    title: (title) => `${title} - ${window.Craft.systemName}`,
    withApp(app) {
      app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');

      app.provide(Queue, queue);
      app.provide(Axios, axios);

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
      app.component('CpLink', CpLink);

      components.install(app);
    },
  });

  handleNonInertiaRequests();

  ensureLegacyNotificationContainer();

  hasBooted = true;
  (window.bootedCallbacks ?? []).forEach((callback) =>
    callback(window.Craft)
  );
  window.bootedCallbacks = [];
}

/**
 * The legacy notifier (`Craft.cp.displayNotification()`, element-copy
 * notifications, …) appends into `#notifications`, which only the Twig layout
 * renders. Create it for Inertia pages — outside the Vue root, so page visits
 * can't clobber legacy-appended notifications — and re-point the CP
 * singleton's cached (empty) reference if it booted before the container
 * existed.
 */
function ensureLegacyNotificationContainer() {
  if (!document.getElementById('notifications')) {
    const container = document.createElement('div');
    container.id = 'notifications';
    container.setAttribute('role', 'status');
    document.body.appendChild(container);
  }

  if (
    window.Craft.cp &&
    !window.Craft.cp.$notificationContainer?.length &&
    window.$
  ) {
    window.Craft.cp.$notificationContainer = window.$('#notifications');
  }
}

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

Object.assign(window.Craft, {
  $queue: queue,
  $axios: axios,
  $inertia: inertiaPageRegistry,
  $components: components,
  booting,
  booted,
  init,
  start,
});

export default window.Craft;
