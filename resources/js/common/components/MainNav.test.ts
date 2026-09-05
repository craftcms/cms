import '@craftcms/ui/components/nav-item/nav-item';
import {expect, it, vi} from 'vite-plus/test';
import {createApp, nextTick, reactive} from 'vue';

const state = vi.hoisted(() => ({
  craftData: null as any,
  page: null as any,
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
  });
  state.page = reactive({
    props: {
      craft: state.craftData,
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

  state.page.props.craft = {
    nav: state.craftData.nav.map((item: any) => ({
      ...item,
      selected: item.url === '/assets',
    })),
  };
  await nextTick();

  const items = Array.from(container.querySelectorAll('craft-nav-item'));
  const entries = items.find((item) => item.textContent?.includes('Entries'));
  const assets = items.find((item) => item.textContent?.includes('Assets'));

  await vi.waitFor(() => {
    expect(
      entries?.shadowRoot?.querySelector('a')?.getAttribute('aria-current')
    ).toBe('false');
    expect(
      assets?.shadowRoot?.querySelector('a')?.getAttribute('aria-current')
    ).toBe('page');
  });

  app.unmount();
  container.remove();
});
