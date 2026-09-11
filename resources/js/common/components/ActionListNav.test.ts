import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {createApp, nextTick} from 'vue';

import ActionList from './ActionList.vue';
import {navItemActions} from '@/common/composables/navActions';
import {navFixture, node, selectFixtureItem} from './nav.fixture';

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
  const {items = navFixture, ...rest} = props as {
    items?: typeof navFixture;
  };

  app = createApp(ActionList, {
    actions: navItemActions(items),
    as: 'craft-nav-item',
    ...rest,
  });
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

it('leaves an icon-less item without a prefix at all', async () => {
  await mount({
    items: [
      node('Branch', {
        href: '/branch',
        icon: 'gear',
        subnav: [
          node('Iconned child', {href: '/branch/1', icon: 'wrench'}),
          node('Bare child', {href: '/branch/2'}),
          node('Heading', {group: true, subnav: [node('Leaf', {href: '/x'})]}),
        ],
      }),
    ],
  });

  // `:scope >` because a branch's subnav is nested inside it — a descendant's
  // prefix would otherwise answer for its parent.
  const prefix = (label: string) =>
    item(label)?.querySelector(':scope > [slot="icon"]');

  // A bullet used to stand in for the missing icon so labels lined up under
  // the icon-bearing rows. They line up on the row's own inset instead now,
  // which leaves nothing to stand in for.
  expect(prefix('Bare child')).toBeNull();
  expect(prefix('Heading')).toBeNull();
  expect(prefix('Leaf')).toBeNull();
  expect(item('Iconned child')?.getAttribute('icon')).toBe('wrench');
});

it("uses a plugin's own icon, which it ships rather than names", async () => {
  const iconSvg = '<svg viewBox="0 0 16 16"><path d="M0 0h16v16H0z"/></svg>';

  await mount({
    items: [
      node('Branch', {
        href: '/branch',
        icon: 'gear',
        subnav: [node('Test Plugin', {href: '/branch/plugin', iconSvg})],
      }),
    ],
  });

  const plugin = item('Test Plugin')!;

  // Through `craft-icon`, so it's sized and coloured like every named icon
  // beside it rather than at whatever size the plugin drew it.
  const icon = plugin.querySelector(':scope > craft-icon[slot="icon"]');
  expect(icon?.querySelector('svg')).not.toBeNull();
});

it('shows a collapsed branch’s children only when you’re in it', async () => {
  await mount({
    iconOnly: true,
    items: [
      node('Entries', {
        href: '/admin/content/entries',
        icon: 'newspaper',
        selected: true,
        subnav: [node('Singles', {href: '/admin/content/entries/singles'})],
      }),
      node('Assets', {
        href: '/admin/assets',
        icon: 'image',
        subnav: [node('Uploads', {href: '/admin/assets/uploads'})],
      }),
    ],
  });

  // The branch you're in indents in place, as stand-in icons — there's no room
  // for labels. Every other branch stays a flyout you have to hover for.
  expect(display('Entries')).toBe('inline');
  expect(item('Singles')?.hasAttribute('icon-only')).toBe(true);

  expect(display('Assets')).toBe('flyout');
  expect(item('Uploads')?.hasAttribute('icon-only')).toBe(false);
});

it('collapses a heading inside a collapsed branch too', async () => {
  await mount({
    iconOnly: true,
    items: [
      node('Entries', {
        href: '/admin/content/entries',
        icon: 'newspaper',
        selected: true,
        subnav: [
          node('Channels', {
            group: true,
            subnav: [node('Posts', {href: '/admin/content/entries/posts'})],
          }),
        ],
      }),
    ],
  });

  // Groups take a different path through `navAttrs` than links do, and it was
  // the one that didn't pass `icon-only` on — so the heading kept its label
  // and its row in a rail that has room for neither.
  const channels = item('Channels');
  expect(channels?.hasAttribute('icon-only')).toBe(true);
  expect(
    channels?.shadowRoot?.querySelector('hr.rail-separator')
  ).not.toBeNull();
  expect(item('Posts')?.hasAttribute('icon-only')).toBe(true);
});

it('renders a destination-less branch as a static item', async () => {
  await mount();

  // `Administration` groups Users/GraphQL/Utilities but isn't a page.
  const administration = item('Administration');

  expect(administration).toBeTruthy();
  expect(administration?.getAttribute('href')).toBeNull();
  expect(item('Users')?.getAttribute('href')).toBe('/admin/users');
});
