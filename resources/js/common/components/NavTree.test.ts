import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {createApp, nextTick} from 'vue';

import NavTree from './NavTree.vue';
import {navFixture} from './NavTree.fixture';

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

it('renders every level of the tree, not just the two the nav had', async () => {
  await mount();

  // Content (0) > Entries (1) > Channels (2, a group) > Blog.
  expect(item('Content')).toBeTruthy();
  expect(item('Entries')).toBeTruthy();
  expect(item('Channels')).toBeTruthy();
  expect(item('Blog')).toBeTruthy();
});

it('flyouts from the configured depth and no earlier', async () => {
  await mount({flyoutFromDepth: 1});

  // The root indents in place; the level below it opens beside the item.
  expect(item('Content')?.getAttribute('subnav-display')).toBe('inline');
  expect(item('Entries')?.getAttribute('subnav-display')).toBe('flyout');
});

it('never flyouts a group, and never counts one as a level', async () => {
  // Depths here: Content 0, Entries 1, Channels 2, Blog 2 — Blog shares its
  // group's depth rather than sitting a level below it. Threshold 3 is what
  // makes that visible: if a group advanced the depth, Blog would be at 3 and
  // would flyout.
  await mount({flyoutFromDepth: 3});

  const channels = item('Channels');

  // A group is a heading inside its parent's flyout, not a flyout of its own.
  expect(channels?.getAttribute('subnav-display')).toBe('inline');
  expect(channels?.hasAttribute('group')).toBe(true);
  expect(item('Blog')?.getAttribute('subnav-display')).toBe('inline');
});

it('renders a destination-less branch as a static item', async () => {
  await mount();

  // `Administration` groups Users/GraphQL/Utilities but isn't a page.
  const administration = item('Administration');

  expect(administration).toBeTruthy();
  expect(administration?.getAttribute('href')).toBeNull();
  expect(item('Users')?.getAttribute('href')).toBe('/admin/users');
});
