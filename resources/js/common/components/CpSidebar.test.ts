import {detectPlatform} from '@tanstack/hotkeys';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {computed, createApp, nextTick, reactive, ref, type App} from 'vue';
import {announcement} from '@/common/announcer';
import {standInCheckVisibility} from '@/test/check-visibility';
import {node} from './nav.fixture';

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

const stack = vi.hoisted(() => ({top: null as unknown}));

vi.mock('@/common/slideouts/panel-stack', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  topStackedPanel: () => stack.top,
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
  stack.top = null;
  announcement.value = null;
});

function appendElement<T extends HTMLElement>(el: T): T {
  document.body.append(el);
  extraElements.push(el);
  return el;
}

async function mountSidebar(
  mode: 'docked' | 'floating',
  visibility: 'visible' | 'hidden',
  nav: unknown[] = []
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
        system: {name: 'Craft CMS', icon: null, logo: null},
        site: null,
        app: {version: '6.0.0', edition: {name: 'Pro'}},
        general: {cpTrigger: 'admin'},
        nav,
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

describe('Mod+Backslash', () => {
  standInCheckVisibility();

  const mod = detectPlatform() === 'mac' ? 'metaKey' : 'ctrlKey';

  function press(init: KeyboardEventInit = {}): void {
    (document.activeElement ?? document.body).dispatchEvent(
      new KeyboardEvent('keydown', {
        key: '\\',
        code: 'Backslash',
        [mod]: true,
        bubbles: true,
        cancelable: true,
        composed: true,
        ...init,
      })
    );
  }

  it('collapses and expands the docked sidebar, leaving focus where it was', async () => {
    await mountSidebar('docked', 'visible');
    const field = appendElement(document.createElement('input'));
    field.focus();

    press();
    await nextTick();

    expect(state.sidebar.visibility).toBe('hidden');
    expect(announcement.value).toBe('Global navigation collapsed');
    await new Promise((resolve) => setTimeout(resolve, 50));
    expect(document.activeElement).toBe(field);

    press();
    await nextTick();

    expect(state.sidebar.visibility).toBe('visible');
    expect(announcement.value).toBe('Global navigation expanded');
    await new Promise((resolve) => setTimeout(resolve, 50));
    expect(document.activeElement).toBe(field);
  });

  it('keeps focus on the nav item it was on while docked', async () => {
    const container = await mountSidebar('docked', 'visible', [
      node('Entries', {href: '/admin/entries', icon: 'newspaper'}),
    ]);
    const item = () =>
      container.querySelector<HTMLElement>('.cp-sidebar__body craft-nav-item')!;
    await (item() as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
    item().focus();
    expect(document.activeElement).toBe(item());

    press();
    await vi.waitFor(() => expect(item().hasAttribute('icon-only')).toBe(true));
    await vi.waitFor(() => expect(document.activeElement).toBe(item()));

    press();
    await vi.waitFor(() =>
      expect(item().hasAttribute('icon-only')).toBe(false)
    );
    await vi.waitFor(() => expect(document.activeElement).toBe(item()));
  });

  it('leaves Mod+Shift+Backslash to the details sidebar', async () => {
    await mountSidebar('docked', 'visible');

    press({shiftKey: true, key: '|'});

    expect(state.sidebar.visibility).toBe('visible');
  });

  it('is left alone while a slideout is open', async () => {
    await mountSidebar('docked', 'visible');
    stack.top = {};

    press();

    expect(state.sidebar.visibility).toBe('visible');
  });
});
