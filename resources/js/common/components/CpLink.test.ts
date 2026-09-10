import {expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import CpLink from './CpLink.vue';

const state = vi.hoisted(() => ({
  visitedHref: null as string | null,
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  router: {
    visit: (href: string) => {
      state.visitedHref = href;
    },
  },
}));

it('renders a linked custom navigation item with its content', async () => {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(
        CpLink,
        {
          as: 'craft-nav-item',
          href: '/settings/assets',
          active: true,
          flush: true,
          block: true,
          icon: 'image',
        },
        {
          default: () => ['Volumes', h('craft-nav-list', {slot: 'subnav'})],
        }
      ),
  });

  app.mount(container);
  await nextTick();

  const link = container.querySelector(':scope > craft-nav-item');

  expect(link?.getAttribute('href')).toBe('/settings/assets');
  expect(link?.hasAttribute('active')).toBe(true);
  expect(link?.hasAttribute('flush')).toBe(true);
  expect(link?.hasAttribute('block')).toBe(true);
  expect(link?.getAttribute('icon')).toBe('image');
  expect(link?.textContent).toContain('Volumes');
  expect(link?.querySelector(':scope > craft-nav-list')).not.toBeNull();

  app.unmount();
  container.remove();
});

it('visits via Inertia when the nav item dispatches craft-navigate', async () => {
  state.visitedHref = null;
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(
        CpLink,
        {as: 'craft-nav-item', href: '/settings/assets'},
        {default: () => 'Volumes'}
      ),
  });

  app.mount(container);
  await nextTick();

  const link = container.querySelector(':scope > craft-nav-item')!;
  const navigateEvent = new CustomEvent('craft-navigate', {
    cancelable: true,
    detail: {href: '/settings/assets'},
  });
  link.dispatchEvent(navigateEvent);

  expect(navigateEvent.defaultPrevented).toBe(true);
  expect(state.visitedHref).toBe('/settings/assets');

  app.unmount();
  container.remove();
});

it('leaves other custom elements on the Inertia Link path untouched', async () => {
  state.visitedHref = null;
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(
        CpLink,
        {as: 'craft-button', href: '/settings/assets'},
        {default: () => 'Volumes'}
      ),
  });

  app.mount(container);
  await nextTick();

  const link = container.querySelector(':scope > craft-button')!;
  const navigateEvent = new CustomEvent('craft-navigate', {
    cancelable: true,
    detail: {href: '/settings/assets'},
  });
  link.dispatchEvent(navigateEvent);

  expect(navigateEvent.defaultPrevented).toBe(false);
  expect(state.visitedHref).toBeNull();

  app.unmount();
  container.remove();
});
