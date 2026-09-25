import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, nextTick, reactive, ref, type App} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
}));

const isLarge = ref(true);

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

vi.mock('@/common/composables/useCpBreakpoints', () => ({
  cpBreakpoints: {greaterOrEqual: () => isLarge},
}));

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    toggle: vi.fn(),
    toggleButton: ref<HTMLElement | null>(null),
  }),
}));

let app: App | null = null;
const extraElements: HTMLElement[] = [];

afterEach(() => {
  app?.unmount();
  app = null;
  extraElements.splice(0).forEach((el) => el.remove());
});

function appendElement<T extends HTMLElement>(el: T): T {
  document.body.append(el);
  extraElements.push(el);
  return el;
}

async function mountTopBar(large: boolean) {
  isLarge.value = large;
  state.page = reactive({
    url: '/admin',
    props: {
      craft: {
        csrfTokenValue: 'token',
        csrfTokenName: 'CRAFT_CSRF_TOKEN',
        system: {name: 'Craft CMS', icon: null},
        app: {
          version: '6.0.0',
          edition: {name: 'Pro', handle: 'pro', value: 2},
        },
        site: null,
        readOnly: false,
        maintenanceMode: false,
        devMode: false,
        allowAdminChanges: true,
        currentUser: {
          username: 'admin',
          email: 'admin@example.com',
          id: 1,
          thumbHtml: null,
          name: 'Admin',
        },
        general: {
          cpTrigger: 'admin',
          actionTrigger: 'actions',
          csrfTokenName: 'CRAFT_CSRF_TOKEN',
          cpLogoUrl: null,
          useEmailAsUsername: false,
          rememberedUserSessionDuration: 0,
          defaultCpLocale: 'en-US',
          notifications: [],
        },
        nav: [],
        navBadges: {},
        orientation: 'ltr',
        actionUrl: 'https://example.test/actions',
        cpUrl: 'https://example.test/admin',
        baseApiUrl: 'https://example.test/api',
      },
    },
  });

  const {default: CpTopBar} = await import('./CpTopBar.vue');
  const container = appendElement(document.createElement('div'));
  app = createApp(CpTopBar);

  app.mount(container);
  await nextTick();

  return container;
}

function sectionClasses(container: HTMLElement) {
  return [...container.querySelectorAll('.cp-top-bar > *')].map(
    (el) => el.className
  );
}

it('orders sections to match the desktop visual layout (breadcrumbs first)', async () => {
  const container = await mountTopBar(true);

  expect(sectionClasses(container)).toEqual([
    'cp-top-bar__breadcrumbs',
    'cp-top-bar__indicators',
    'cp-top-bar__end',
  ]);
});

it('orders sections to match the mobile visual layout (breadcrumbs last)', async () => {
  const container = await mountTopBar(false);

  expect(sectionClasses(container)).toEqual([
    'cp-top-bar__start',
    'cp-top-bar__indicators',
    'cp-top-bar__end',
    'cp-top-bar__breadcrumbs',
  ]);
});
