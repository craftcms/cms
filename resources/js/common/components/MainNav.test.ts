import {expect, it, vi} from 'vite-plus/test';
import {createApp, nextTick, reactive} from 'vue';

const state = vi.hoisted(() => ({
  page: null as any,
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

it('updates the active item when the shared navigation changes', async () => {
  const {default: MainNav} = await import('./MainNav.vue');
  const craftData = {
    nav: [
      {
        label: 'Entries',
        url: '/entries',
        icon: null,
        selected: true,
        badgeCount: null,
        external: false,
        subnav: false,
      },
      {
        label: 'Assets',
        url: '/assets',
        icon: null,
        selected: false,
        badgeCount: null,
        external: false,
        subnav: false,
      },
    ],
  };
  state.page = reactive({
    props: {
      craft: craftData,
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

  state.page.props = {
    ...state.page.props,
    craft: {
      ...craftData,
      nav: craftData.nav.map((item) => ({
        ...item,
        selected: item.url === '/assets',
      })),
    },
  };
  await nextTick();

  const items = Array.from(container.querySelectorAll('craft-nav-item'));
  const entries = items.find((item) => item.textContent?.includes('Entries'));
  const assets = items.find((item) => item.textContent?.includes('Assets'));

  expect((entries as any).active).toBe(false);
  expect((assets as any).active).toBe(true);

  app.unmount();
  container.remove();
});
