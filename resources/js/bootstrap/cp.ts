import {createInertiaApp, router} from '@inertiajs/vue3';
import type {DefineComponent} from 'vue';
import axios from 'axios';
import {setTranslations, t} from '@craftcms/ui/utilities/translate';
import {setUrlDefaults} from '@/wayfinder';
import {inertiaPageRegistry, resolveInertiaPage} from './inertia-pages.js';
import AppLayout from '@/common/layouts/AppLayout.vue';
import {registerSlideoutGlobals} from '@/common/slideouts';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import {configureIcons} from './icons.js';
import {config, installCpApp, queue} from './cp-app';
import {cpComponentRegistry} from './components.js';
import type {ScreenPageProps} from '@/common/composables/screen';

type TranslationStore = Record<string, Record<string, string>>;

interface CpInitialConfig {
  translations?: TranslationStore;
  [key: string]: ScreenPageProps[string] | TranslationStore;
}

let bootedCallbacks: Array<(instance: typeof window.Cp) => void> = [];
let bootingCallbacks: Array<(instance: typeof window.Cp) => void> = [];

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

function routeSegment(value: string): string {
  return value.toString().replace(/^\/+|\/+$/g, '');
}

const initialConfig: CpInitialConfig | typeof window.Craft = {};

// Create our object
const Cp = {
  initialConfig,

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
    return cpComponentRegistry;
  },

  booted(callback: (instance: typeof window.Cp) => void) {
    bootedCallbacks.push(callback);
  },

  booting(callback: (instance: typeof window.Cp) => void) {
    bootingCallbacks.push(callback);
  },

  config(config: CpInitialConfig | typeof window.Craft) {
    this.initialConfig = config;
  },

  init() {
    config.initialize(this.initialConfig);
    configureIcons(config.get('iconBaseUrl', '/vendor/craft/icons'));

    setUrlDefaults(() => ({
      cpTrigger: routeSegment(String(config.get('cpTrigger') ?? '')),
      actionTrigger: routeSegment(String(config.get('actionTrigger') ?? '')),
    }));

    queue.initialize({
      runAutomatically: config.get('runQueueAutomatically', true),
      enabled: true,
      appId: config.get('systemUid', ''),
      canAccessQueueManager: config.get('canAccessQueueManager', false),
    });

    setTranslations(this.initialConfig.translations ?? {});
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
      resolve: async (name) => {
        const page = await resolveInertiaPage(name);

        // SAFETY: the registry only accepts Vue defineComponent/SFC pages.
        return page as DefineComponent;
      },
      layout: defaultPageLayout,
      title: (title) => `${title} - ${this.$config.get('systemName')}`,
      withApp(app) {
        installCpApp(app);
      },
    });

    handleNonInertiaRequests();
    handleAccessibleRouting();
    ensureLegacyNotificationContainer();
    registerSlideoutGlobals();

    console.log('Calling booted callbacks', bootedCallbacks);
    bootedCallbacks.forEach((callback) => callback(this));
    bootedCallbacks = [];
  },
};

/**
 * When navigating to a new page, set keyboard focus on the route focus anchor and
 * announce route change.
 */
function handleAccessibleRouting() {
  const {announce} = useAnnouncer();
  let previousPathname: string | null = null;
  router.on('navigate', (event) => {
    const {props, url} = event.detail.page;
    const pathname = new URL(url, window.location.origin).pathname;
    if (pathname === previousPathname) return;
    previousPathname = pathname;

    const routeFocusAnchor: HTMLElement | null =
      document.getElementById('route-focus-anchor');
    routeFocusAnchor?.focus();

    if (!props.title) return;

    announce(t('Navigated to {title} page', {title: props.title}));
  });
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

  if (Craft.cp && !Craft.cp.$notificationContainer?.length && window.$) {
    Craft.cp.$notificationContainer = $('#notifications');
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

export default Cp;
