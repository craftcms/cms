import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import CpLink from './CpLink.vue';

function dispatchClick(
  target: Element,
  init: MouseEventInit
): {defaultPrevented: boolean} {
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    composed: true,
    button: 0,
    ...init,
  });
  target.dispatchEvent(event);
  return {defaultPrevented: event.defaultPrevented};
}

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

it('lets modified and auxiliary clicks on a custom-element link fall through to the browser default', async () => {
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

  const link = container.querySelector(
    ':scope > craft-nav-item'
  ) as HTMLElement;

  for (const init of [
    {ctrlKey: true},
    {metaKey: true},
    {shiftKey: true},
    {altKey: true},
    {button: 1},
  ] satisfies MouseEventInit[]) {
    link.dispatchEvent(
      new MouseEvent('mousedown', {bubbles: true, cancelable: true, ...init})
    );
    link.dispatchEvent(
      new MouseEvent('mouseup', {bubbles: true, cancelable: true, ...init})
    );
    const {defaultPrevented} = dispatchClick(link, init);
    expect(defaultPrevented).toBe(false);
  }

  app.unmount();
  container.remove();
});
