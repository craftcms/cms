import {QueueService} from '@craftcms/cp';
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
import {setTranslations} from '@craftcms/cp/utilities/translate.ts.mjs';

const queue = QueueService.getInstance();
let hasBooted = false;

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
  queue.initialize({
    runAutomatically: window.Craft.runQueueAutomatically ?? true,
    enabled: true,
    appId: window.Craft.systemUid ?? '',
    canAccessQueueManager: window.Craft.canAccessQueueManager ?? false,
  });

  setTranslations(window.Craft.translations);
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
    pages: '../pages',
    title: (title) => `${title} - ${window.Craft.systemName}`,
    withApp(app) {
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
    },
  });

  handleNonInertiaRequests();

  hasBooted = true;
  (window.bootedCallbacks ?? []).forEach((callback) =>
    callback(window.Craft)
  );
  window.bootedCallbacks = [];
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
  booting,
  booted,
  init,
  start,
});

export default window.Craft;
