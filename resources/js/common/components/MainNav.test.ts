import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, nextTick, reactive} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

let container: HTMLElement;
let app: ReturnType<typeof createApp> | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  container?.remove();
});

/** The nav as the server now sends it: no selection, no badge counts. */
const nav = () => [
  {
    label: 'Entries',
    href: '/admin/content/entries',
    id: 'nav-admin-content-entries',
    icon: null,
    selected: false,
    badgeCount: 0,
    external: false,
    group: false,
    subnav: false,
  },
  {
    label: 'Utilities',
    href: '/admin/utilities',
    id: 'nav-admin-utilities',
    icon: null,
    selected: false,
    badgeCount: 0,
    external: false,
    group: false,
    subnav: false,
  },
];

async function mount(url: string, navBadges: Record<string, number> = {}) {
  const {default: MainNav} = await import('./MainNav.vue');

  state.page = reactive({
    url,
    props: {
      craft: {nav: nav(), navBadges},
      queue: {
        displayedJob: null,
        hasReservedJobs: false,
        hasWaitingJobs: false,
      },
    },
  });

  container = document.createElement('div');
  document.body.append(container);
  app = createApp(MainNav);
  app.mount(container);
  await nextTick();

  return Array.from(container.querySelectorAll('craft-nav-item'));
}

const byLabel = (items: Array<Element>, label: string) =>
  items.find((item) => item.textContent?.includes(label)) as any;

it('follows the url when the page changes, not a server-set flag', async () => {
  // The tree is a once-prop now: it's sent on the first response and held, so
  // it carries no selection and the same objects are still here on the next
  // page. Only the url tells the sidebar where it is.
  const items = await mount('/admin/content/entries');

  expect(byLabel(items, 'Entries').active).toBe(true);
  expect(byLabel(items, 'Utilities').active).toBe(false);

  state.page.url = '/admin/utilities/system-report';
  await nextTick();

  expect(byLabel(items, 'Entries').active).toBe(false);
  expect(byLabel(items, 'Utilities').active).toBe(true);
});

it('still re-reads the nav when the page prop is replaced', async () => {
  // Inertia hands over a whole new props object on each visit, and MainNav
  // lives in the sidebar and never remounts — so that object has to be the
  // computed's dependency or the sidebar keeps whichever nav it first drew.
  await mount('/admin/content/entries');

  state.page.props = {
    ...state.page.props,
    craft: {
      nav: [{...nav()[0], label: 'Renamed'}],
      navBadges: {},
    },
  };
  await nextTick();

  const items = Array.from(container.querySelectorAll('craft-nav-item'));

  expect(items.map((item) => item.textContent?.trim())).toEqual(['Renamed']);
});

it('applies badge counts that arrive alongside the tree', async () => {
  // Counts change without the tree changing, so they ride separately.
  const items = await mount('/admin/content/entries', {
    'nav-admin-utilities': 3,
  });

  expect(byLabel(items, 'Utilities').indicator).toBe(true);
  expect(byLabel(items, 'Entries').indicator).toBe(false);
});

it('links each item where it says it does', async () => {
  // Without this, a rename that missed the template left every item pointing
  // at the current page: clicking one reloaded rather than navigating, and
  // nothing failed. Property when the element has upgraded, attribute when
  // it hasn't.
  const items = await mount('/admin/content/entries');

  expect(
    items.map((item) => (item as any).href ?? item.getAttribute('href'))
  ).toEqual(['/admin/content/entries', '/admin/utilities']);
});
