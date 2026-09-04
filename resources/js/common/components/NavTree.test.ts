import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {createApp, nextTick} from 'vue';

import NavTree from './NavTree.vue';
import {navFixture, selectFixtureItem} from './NavTree.fixture';

let container: HTMLElement;
let app: ReturnType<typeof createApp> | null = null;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
});

// Mounted into the document rather than a detached node, so the custom
// elements upgrade — which means it has to be taken back out again.
afterEach(() => {
  app?.unmount();
  app = null;
  container.remove();
});

function mount(props: Record<string, unknown> = {}) {
  app = createApp(NavTree, {items: navFixture, ...props});
  app.mount(container);

  return nextTick();
}

/** The nav item whose own label is `label`, ignoring its descendants' text. */
function item(label: string): Element | undefined {
  return Array.from(container.querySelectorAll('craft-nav-item')).find(
    (el) =>
      Array.from(el.childNodes)
        .filter((node) => node.nodeType === Node.TEXT_NODE)
        .map((node) => node.textContent?.trim())
        .join('') === label
  );
}

const display = (label: string) => item(label)?.getAttribute('subnav-display');

it('renders every level of the tree, not just the two the nav had', async () => {
  await mount();

  // Content > Entries > Channels (a group) > Blog.
  expect(item('Content')).toBeTruthy();
  expect(item('Entries')).toBeTruthy();
  expect(item('Channels')).toBeTruthy();
  expect(item('Blog')).toBeTruthy();
});

it('expands the trail to the selection and flyouts everything else', async () => {
  // The fixture selects `Blog`, so the trail is Content > Entries > Channels.
  await mount();

  expect(display('Content')).toBe('inline');
  expect(display('Entries')).toBe('inline');
  expect(item('Content')?.getAttribute('initial-state')).toBe('open');
  expect(item('Entries')?.getAttribute('initial-state')).toBe('open');

  // Off the trail, at the root and one level down.
  expect(display('Administration')).toBe('flyout');
  expect(display('Settings')).toBe('flyout');
  expect(display('Assets')).toBe('flyout');
});

it('moves the expansion when the selection moves', async () => {
  await mount({items: selectFixtureItem('Utilities')});

  // `Utilities` sits under `Administration`, so that branch opens up...
  expect(display('Administration')).toBe('inline');
  // ...and the one that was expanded closes back into a flyout.
  expect(display('Content')).toBe('flyout');
  expect(display('Entries')).toBe('flyout');
});

it('never flyouts a group, wherever the group itself landed', async () => {
  // Off the trail entirely, so `Channels` is inside a flyout here — and a
  // flyout within a flyout is not a thing. It renders inline inside it.
  await mount({items: selectFixtureItem('Utilities')});

  const channels = item('Channels');

  expect(channels?.hasAttribute('group')).toBe(true);
  expect(channels?.getAttribute('subnav-display')).toBe('inline');
});

it('forces one mode or the other when asked', async () => {
  await mount({mode: 'flyout'});
  // On the trail, but `flyout` overrides that.
  expect(display('Entries')).toBe('flyout');

  app?.unmount();
  container.replaceChildren();

  await mount({mode: 'inline'});
  // Off the trail, but `inline` overrides that.
  expect(display('Settings')).toBe('inline');
});

it('renders a destination-less branch as a static item', async () => {
  await mount();

  // `Administration` groups Users/GraphQL/Utilities but isn't a page.
  const administration = item('Administration');

  expect(administration).toBeTruthy();
  expect(administration?.getAttribute('href')).toBeNull();
  expect(item('Users')?.getAttribute('href')).toBe('/admin/users');
});
