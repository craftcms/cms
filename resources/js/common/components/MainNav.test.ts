import {expect, it, vi} from 'vite-plus/test';
import {createApp, nextTick, reactive} from 'vue';

const state = vi.hoisted(() => ({
  craftData: null as any,
  page: null as any,
}));

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => state.craftData,
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

it('updates the active item when the shared navigation changes', async () => {
  const {default: MainNav} = await import('./MainNav.vue');
  state.craftData = reactive({
    nav: [
      {
        label: 'Entries',
        href: '/entries',
        icon: null,
        selected: true,
        badgeCount: null,
        external: false,
        subnav: false,
      },
      {
        label: 'Assets',
        href: '/assets',
        icon: null,
        selected: false,
        badgeCount: null,
        external: false,
        subnav: false,
      },
    ],
  });
  state.page = reactive({
    props: {
      queue: {
        displayedJob: null,
        hasReservedJobs: false,
        hasWaitingJobs: false,
      },
    },
  });
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp(MainNav);

  app.mount(container);
  await nextTick();

  state.craftData.nav = state.craftData.nav.map((item: any) => ({
    ...item,
    selected: item.href === '/assets',
  }));
  await nextTick();

  const items = Array.from(container.querySelectorAll('craft-nav-item'));
  const entries = items.find((item) => item.textContent?.includes('Entries'));
  const assets = items.find((item) => item.textContent?.includes('Assets'));

  expect((entries as any).active).toBe(false);
  expect((assets as any).active).toBe(true);

  // Each item links where it says it does. Without this, a rename that missed
  // the template left every item pointing at the current page: clicking one
  // reloaded rather than navigating, and nothing failed.
  // Property when the element has upgraded, attribute when it hasn't.
  expect(
    items.map((item) => (item as any).href ?? item.getAttribute('href'))
  ).toEqual(['/entries', '/assets']);

  app.unmount();
  container.remove();
});
