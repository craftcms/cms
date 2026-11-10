import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {computed, createApp, nextTick, reactive, ref, type App} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
  sidebar: null as unknown as {
    mode: 'docked' | 'floating';
    visibility: 'visible' | 'hidden';
  },
  toggle: vi.fn(),
  toggleButton: null as unknown as {value: HTMLElement | null},
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
    close: () => {
      state.sidebar.visibility = 'hidden';
    },
    toggleButton: state.toggleButton,
    icon: computed(() => 'arrow-left-to-line'),
  }),
}));

// Below `lg` the sidebar renders its header, with the close toggle the floating
// focus test expects to land on.
vi.mock('@/common/composables/useCpBreakpoints', () => ({
  cpBreakpoints: {greaterOrEqual: () => ref(false)},
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
  state.toggleButton = ref(null);
  state.toggle = vi.fn(() => {
    state.sidebar.visibility =
      state.sidebar.visibility === 'visible' ? 'hidden' : 'visible';
  });
  state.page = reactive({
    url: '/admin',
    props: {
      craft: {
        maintenanceMode: false,
        system: {name: 'Craft CMS', icon: null},
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
  const container = appendElement(document.createElement('div'));
  app = createApp(CpSidebar);

  app.mount(container);
  await nextTick();

  return container;
}

it('keeps focus on the collapse item when the docked sidebar expands', async () => {
  const container = await mountSidebar('docked', 'hidden');
  const collapseItem = () =>
    container.querySelector<HTMLElement>('.cp-sidebar__footer craft-nav-item')!;

  collapseItem().click();

  expect(state.sidebar.visibility).toBe('visible');
  await vi.waitFor(() => expect(document.activeElement).toBe(collapseItem()));
});

it('keeps focus on the collapse item when the docked sidebar collapses', async () => {
  const container = await mountSidebar('docked', 'visible');
  const collapseItem = () =>
    container.querySelector<HTMLElement>('.cp-sidebar__footer craft-nav-item')!;

  collapseItem().click();

  expect(state.sidebar.visibility).toBe('hidden');
  await vi.waitFor(() => expect(document.activeElement).toBe(collapseItem()));
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

describe('floating', () => {
  function addTo(container: HTMLElement): HTMLButtonElement {
    const button = document.createElement('button');
    container.append(button);

    return button;
  }

  it('closes on a click elsewhere on the page', async () => {
    const container = await mountSidebar('floating', 'visible');

    addTo(container).click();

    expect(state.sidebar.visibility).toBe('hidden');
  });

  it('stays open on a click inside it', async () => {
    const container = await mountSidebar('floating', 'visible');

    container.querySelector<HTMLElement>('.cp-sidebar__body')!.click();

    expect(state.sidebar.visibility).toBe('visible');
  });

  it('leaves its toggle to toggle it', async () => {
    const container = await mountSidebar('floating', 'visible');
    const toggle = addTo(container);
    state.toggleButton.value = toggle;
    toggle.addEventListener('click', state.toggle);

    toggle.click();

    expect(state.sidebar.visibility).toBe('hidden');
    toggle.click();
    expect(state.sidebar.visibility).toBe('visible');
  });

  it('stays open on a click in something opened over the page', async () => {
    await mountSidebar('floating', 'visible');

    appendElement(document.createElement('button')).click();

    expect(state.sidebar.visibility).toBe('visible');
  });
});

it('stays open on a click elsewhere while docked', async () => {
  const container = await mountSidebar('docked', 'visible');
  const button = document.createElement('button');
  container.append(button);

  button.click();

  expect(state.sidebar.visibility).toBe('visible');
});
