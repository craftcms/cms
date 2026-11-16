import '../../../css/cp.css';
import '@craftcms/ui/components/nav-list/nav-list';
import '@craftcms/ui/components/nav-item/nav-item';
import {createApp, nextTick} from 'vue';
import {router} from '@inertiajs/vue3';
import {expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import ActionList from './ActionList.vue';

it('visits an item only when its own row is pressed, not the space in its subnav', async () => {
  vi.spyOn(router, 'prefetch').mockImplementation(() => {});
  const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
  const container = document.createElement('div');
  container.style.width = '240px';
  document.body.append(container);
  const app = createApp(ActionList, {
    as: 'craft-nav-item',
    actions: [
      {
        type: 'link',
        label: 'Utilities',
        href: '/admin/utilities',
        selected: true,
        subnav: [
          {type: 'link', label: 'Updates', href: '/admin/utilities/updates'},
          {
            type: 'link',
            label: 'System Report',
            href: '/admin/sr',
            selected: true,
          },
        ],
      },
    ],
  });
  app.mount(container);
  await nextTick();
  const utilities = container.querySelector('craft-nav-item')!;
  const updates = utilities.querySelector('craft-nav-item')!;
  await utilities.updateComplete;
  const host = utilities.getBoundingClientRect();
  const subnav = utilities.shadowRoot!.querySelector('.subnav')!;
  const left = subnav.getBoundingClientRect().left - host.left + 3;
  const y = updates.getBoundingClientRect().bottom - host.top - 10;

  await userEvent.click(utilities, {position: {x: left, y}});
  await userEvent.click(utilities, {position: {x: host.width - 1, y}});
  await userEvent.click(updates);
  await userEvent.click(utilities, {position: {x: 4, y: 4}});

  expect(visit.mock.calls.map(([url]) => url)).toEqual([
    '/admin/utilities/updates',
    '/admin/utilities',
  ]);
  app.unmount();
  container.remove();
});
