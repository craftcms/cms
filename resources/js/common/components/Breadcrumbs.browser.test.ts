import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {router} from '@inertiajs/vue3';
import type CraftActionMenu from '@craftcms/ui/components/action-menu/action-menu';
import type {ActionMenuItem} from '@craftcms/ui/components/action-menu/action-menu.types';
import '../../../css/cp.css';
import '@craftcms/ui/components/breadcrumbs/breadcrumbs';
import '@craftcms/ui/components/breadcrumb-item/breadcrumb-item';
import '@craftcms/ui/components/action-menu/action-menu';
import '@craftcms/ui/components/button/button';
import Breadcrumbs from './Breadcrumbs.vue';

let app: App | undefined;

afterEach(() => {
  vi.restoreAllMocks();
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

it('gives the section switcher a 24×24 target that stays clear of the links beside it', async () => {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(Breadcrumbs, {
        items: [
          {label: 'Entries', href: '/admin/content/entries'},
          {
            label: 'Singles',
            href: '/admin/content/entries/singles',
            items: [{type: 'link', label: 'Channels', href: '/admin/channels'}],
          },
          {label: 'My Homepage', href: '/admin/content/entries/singles/2'},
        ],
      }),
  });
  app.mount(container);
  await nextTick();

  const switcher = container.querySelector<HTMLElement>(
    'craft-action-menu > [slot="invoker"]'
  )!;
  await (switcher as HTMLElement & {updateComplete: Promise<unknown>})
    .updateComplete;
  const box = switcher.getBoundingClientRect();

  expect(box.width).toBeGreaterThanOrEqual(24);
  expect(box.height).toBeGreaterThanOrEqual(24);

  for (const link of container.querySelectorAll('craft-breadcrumb-item a')) {
    const other = link.getBoundingClientRect();
    const overlaps =
      box.left < other.right &&
      other.left < box.right &&
      box.top < other.bottom &&
      other.top < box.bottom;
    expect(overlaps, link.textContent ?? '').toBe(false);
  }
});

it('visits a collapsed crumb’s page when it’s chosen from the overflow menu', async () => {
  const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
  vi.spyOn(router, 'prefetch').mockImplementation(() => {});
  const container = document.createElement('div');
  container.style.inlineSize = '120px';
  document.body.append(container);
  app = createApp({
    render: () =>
      h(Breadcrumbs, {
        items: [
          {label: 'Entries', href: '/admin/content/entries'},
          {label: 'Singles', href: '/admin/content/entries/singles'},
          {label: 'My Homepage With A Long Title'},
        ],
      }),
  });
  app.mount(container);
  await nextTick();
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const menu = container
    .querySelector('craft-breadcrumbs')!
    .shadowRoot!.querySelector<CraftActionMenu>('.breadcrumbs__menu')!;
  // The menu reads its items when it opens.
  const actions =
    typeof menu.actions === 'function' ? menu.actions() : menu.actions!;
  const singles = actions.find(
    (action) => 'label' in action && action.label === 'Singles'
  ) as {onClick: (event: Event) => void};
  expect(singles).toBeDefined();

  singles.onClick(new MouseEvent('click', {cancelable: true}));

  expect(visit).toHaveBeenCalledTimes(1);
  expect(String(visit.mock.calls[0]![0])).toContain(
    '/admin/content/entries/singles'
  );
});
