import {expect, it, vi} from 'vite-plus/test';
import {computed, createApp, nextTick, reactive} from 'vue';

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
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp(CpSidebar);

  app.mount(container);
  await nextTick();

  return {
    container,
    unmount: () => {
      app.unmount();
      container.remove();
    },
  };
}

it('returns focus to the relocated toggle when the docked sidebar expands', async () => {
  const {container, unmount} = await mountSidebar('docked', 'hidden');

  container.querySelector<HTMLElement>('#sidebar-toggle')!.click();
  await nextTick();
  await nextTick();

  expect(state.sidebar.visibility).toBe('visible');
  expect(document.activeElement).toBe(
    container.querySelector('#sidebar-toggle')
  );

  unmount();
});

it('moves focus to the nav body instead of the toggle when the docked sidebar collapses', async () => {
  const {container, unmount} = await mountSidebar('docked', 'visible');

  container.querySelector<HTMLElement>('#sidebar-toggle')!.click();
  await nextTick();
  await nextTick();

  expect(state.sidebar.visibility).toBe('hidden');
  expect(document.activeElement).toBe(
    container.querySelector('.cp-sidebar__body')
  );

  unmount();
});

it('leaves focus on the toggle when a floating sidebar opens or closes', async () => {
  const {container, unmount} = await mountSidebar('floating', 'hidden');
  const toggleButton = container.querySelector<HTMLElement>('#sidebar-toggle')!;

  toggleButton.click();
  await nextTick();
  await nextTick();

  expect(state.sidebar.visibility).toBe('visible');
  expect(document.activeElement).toBe(toggleButton);

  unmount();
});
