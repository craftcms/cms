import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {computed, createApp, reactive, ref, type App} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
  sidebar: null as unknown as {
    mode: 'docked' | 'floating';
    visibility: 'visible' | 'hidden';
  },
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    sidebar: state.sidebar,
    collapsed: computed(() => false),
    toggle: () => {},
    close: () => {},
    toggleButton: ref(null),
    icon: computed(() => 'arrow-left-to-line'),
  }),
}));

vi.mock('@/common/composables/useCpBreakpoints', () => ({
  cpBreakpoints: {greaterOrEqual: () => ref(false)},
}));

let app: App | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  document.body.innerHTML = '';
  document.documentElement.removeAttribute('dir');
});

async function mountFloating(
  visibility: 'visible' | 'hidden',
  dir: 'ltr' | 'rtl'
): Promise<HTMLElement> {
  document.documentElement.dir = dir;
  state.sidebar = reactive({mode: 'floating', visibility});
  state.page = reactive({
    url: '/admin',
    props: {
      craft: {
        maintenanceMode: false,
        system: {name: 'Craft CMS', icon: null, logo: null},
        site: null,
        app: {version: '6.0.0', edition: {name: 'Pro'}},
        general: {cpTrigger: 'admin'},
        nav: [],
        navBadges: {},
      },
      queue: {
        displayedJob: null,
        hasReservedJobs: false,
        hasWaitingJobs: false,
      },
    },
  });

  const {default: CpSidebar} = await import('./CpSidebar.vue');
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(CpSidebar);
  app.mount(container);
  await new Promise((resolve) => setTimeout(resolve, 300));

  return document.querySelector<HTMLElement>('.cp-sidebar')!;
}

describe.each(['ltr', 'rtl'] as const)('floating, in %s', (dir) => {
  it('sits off the page, hidden, while it’s closed', async () => {
    const sidebar = await mountFloating('hidden', dir);
    const rect = sidebar.getBoundingClientRect();

    expect(getComputedStyle(sidebar).visibility).toBe('hidden');
    expect(rect.right <= 0 || rect.left >= innerWidth).toBe(true);
  });

  it('sits on the page, visible, while it’s open', async () => {
    const sidebar = await mountFloating('visible', dir);
    const rect = sidebar.getBoundingClientRect();

    expect(getComputedStyle(sidebar).visibility).toBe('visible');
    expect(rect.left >= 0 && rect.right <= innerWidth).toBe(true);
  });
});
