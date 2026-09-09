import {afterEach, expect, it, vi} from 'vite-plus/test';
import {computed, createApp, nextTick, reactive, type App} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
  sidebar: null as unknown as {
    mode: 'docked' | 'floating';
    visibility: 'visible' | 'hidden';
  },
  toggle: vi.fn(),
}));
state.sidebar = reactive({mode: 'docked', visibility: 'visible'});

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    sidebar: state.sidebar,
    collapsed: computed(
      () =>
        state.sidebar.mode === 'docked' && state.sidebar.visibility === 'hidden'
    ),
    toggle: state.toggle,
    icon: computed(() => 'arrow-left-to-line'),
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

async function mountSidebar(
  mode: 'docked' | 'floating',
  visibility: 'visible' | 'hidden'
) {
  state.sidebar = reactive({mode, visibility});
  state.toggle = vi.fn(() => {
    state.sidebar.visibility =
      state.sidebar.visibility === 'visible' ? 'hidden' : 'visible';
  });
  state.page = reactive({
    props: {
      craft: {
        maintenanceMode: false,
        system: {name: 'Craft CMS', icon: null},
        site: null,
        app: {version: '6.0.0', edition: {name: 'Pro'}},
        general: {cpTrigger: 'admin'},
        nav: [],
      },
      queue: {
        displayedJob: null,
        hasReservedJobs: false,
        hasWaitingJobs: false,
      },
    },
  });

  const {default: CpSidebar} = await import('./CpSidebar.vue');
  const container = appendElement(document.createElement('div'));
  app = createApp(CpSidebar);

  app.mount(container);
  await nextTick();

  return container;
}

it('returns focus to the relocated toggle when the docked sidebar expands', async () => {
  const container = await mountSidebar('docked', 'hidden');

  container.querySelector<HTMLElement>('#sidebar-toggle')!.click();

  expect(state.sidebar.visibility).toBe('visible');
  await vi.waitFor(() =>
    expect(document.activeElement).toBe(
      container.querySelector('#sidebar-toggle')
    )
  );
});

it('moves focus to the nav body instead of the toggle when the docked sidebar collapses', async () => {
  const container = await mountSidebar('docked', 'visible');

  container.querySelector<HTMLElement>('#sidebar-toggle')!.click();

  expect(state.sidebar.visibility).toBe('hidden');
  await vi.waitFor(() =>
    expect(document.activeElement).toBe(
      container.querySelector('.cp-sidebar__body')
    )
  );
});

it('moves focus into the sidebar when it becomes visible while focus was on an external control', async () => {
  const container = await mountSidebar('floating', 'hidden');
  const externalControl = appendElement(document.createElement('button'));
  externalControl.focus();

  state.sidebar.visibility = 'visible';
  await vi.waitFor(() =>
    expect(document.activeElement).toBe(
      container.querySelector('#sidebar-toggle')
    )
  );
});
