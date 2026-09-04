import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {ActionItems} from '@/common/types';
import SecondaryNav from './SecondaryNav.vue';

const realMatchMedia = window.matchMedia;

/**
 * The nav picks its layout from a media query, so each test says which one
 * it's exercising before mounting. Stubbing `matchMedia` rather than
 * `useMediaQuery` keeps VueUse in the loop.
 */
function stubViewport(isLarge: boolean): void {
  window.matchMedia = ((media: string) => ({
    media,
    matches: isLarge,
    onchange: null,
    addEventListener: () => {},
    removeEventListener: () => {},
    addListener: () => {},
    removeListener: () => {},
    dispatchEvent: () => false,
  })) as unknown as typeof window.matchMedia;
}

let teardown: (() => void) | undefined;

const ACTIONS: ActionItems = [
  {label: 'New Group', icon: 'plus', onClick: () => {}},
];

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

function navItem(config: Partial<NavItem> & {label: string}): NavItem {
  return {
    href: `/admin/${config.label.toLowerCase().replace(/\s+/g, '-')}`,
    selected: false,
    subnav: false,
    group: false,
    external: false,
    badgeCount: 0,
    ariaLabel: null,
    icon: null,
    id: null,
    fontIcon: null,
    linkAttributes: {},
    ...config,
  } as NavItem;
}

function mountWith(items: Array<NavItem>): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({render: () => h(SecondaryNav, {items})});
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  return container;
}

function mount(): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({
    render: () => h(SecondaryNav, {items: [], actions: ACTIONS}),
  });
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  return container;
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
  window.matchMedia = realMatchMedia;
});

describe('SecondaryNav', () => {
  it('links its items while the nav is expanded', async () => {
    stubViewport(true);

    const container = mountWith([
      navItem({label: 'Volumes', href: '/admin/settings/assets'}),
      navItem({
        label: 'Account Security',
        group: true,
        subnav: [navItem({label: 'Password', href: '/admin/password'})],
      }),
    ]);
    await nextTick();

    // The expanded list is a separate rendering from the collapsed menu, so
    // it needs its own guard — a rename that missed it left every item in the
    // docked sidebar linking nowhere while every test still passed.
    // `href` is a non-reflecting Lit property, so read the property.
    expect(
      [...container.querySelectorAll('craft-nav-item')]
        .map((item) => (item as HTMLElement & {href?: string}).href)
        .filter(Boolean)
    ).toEqual(['/admin/settings/assets', '/admin/password']);
  });

  it('lists the nav items in the menu it collapses into', async () => {
    stubViewport(false);

    const container = mountWith([
      navItem({label: 'Deprecation Warnings', icon: 'bug', selected: true}),
      navItem({label: 'Queue Manager', icon: 'list-check'}),
    ]);
    await nextTick();

    const items = [...container.querySelectorAll('craft-action-item')] as Array<
      HTMLElement & {icon?: string}
    >;

    // The menu is built from the items, so a page that draws its own nav into
    // the default slot instead leaves this empty — which is what the utilities
    // screens did.
    expect(items.map((item) => item.textContent?.trim())).toEqual([
      'Deprecation Warnings',
      'Queue Manager',
    ]);
    expect(items.map((item) => item.icon)).toEqual(['bug', 'list-check']);
  });

  it('marks the current item the way the breadcrumb switcher does', async () => {
    stubViewport(false);

    const container = mountWith([
      navItem({label: 'Deprecation Warnings', icon: 'bug', selected: true}),
      navItem({label: 'Queue Manager', icon: 'list-check'}),
    ]);
    await nextTick();

    const items = [...container.querySelectorAll('craft-action-item')] as Array<
      HTMLElement & {type?: string; checked?: boolean}
    >;

    // Both menus come from `navItemActions` through `ActionList`, so they mark
    // the current item and show icons alike rather than drifting apart.
    expect(items.map((item) => item.type)).toEqual(['checkbox', 'checkbox']);
    expect(items.map((item) => item.checked)).toEqual([true, false]);
  });

  it('gutters the whole collapsed menu once the nav is in it', async () => {
    stubViewport(false);

    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(SecondaryNav, {
          items: [navItem({label: 'Deprecation Warnings', selected: true})],
          actions: ACTIONS,
        }),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    // The nav and its controls are one list, so the checkmark gutter is
    // decided once — otherwise the labels below the rule sit at a different
    // indent from the ones above it.
    expect(
      [...container.querySelectorAll('craft-action-item')].map(
        (item) => (item as HTMLElement & {type?: string}).type
      )
    ).toEqual(['checkbox', 'checkbox']);
  });

  it('stands a group aside for its children in the collapsed menu', async () => {
    stubViewport(false);

    const container = mountWith([
      navItem({label: 'Profile'}),
      navItem({
        label: 'Account Security',
        href: '#',
        group: true,
        subnav: [navItem({label: 'Password'}), navItem({label: 'Passkeys'})],
      }),
    ]);
    await nextTick();

    // A group heads its children rather than being somewhere to go — its own
    // URL is '#', so listing it would offer a link to nowhere.
    expect(
      [...container.querySelectorAll('craft-action-item')].map((item) =>
        item.textContent?.trim()
      )
    ).toEqual(['Profile', 'Password', 'Passkeys']);
  });

  it('renders its actions as buttons while the nav is expanded', async () => {
    stubViewport(true);

    const container = mount();
    await nextTick();

    const button = container.querySelector(
      '.secondary-nav__actions craft-button'
    );

    expect(button?.textContent).toContain('New Group');
    expect(container.querySelector('craft-action-menu')).toBeNull();
  });

  it('keeps a later action inside the menu it belongs to', async () => {
    stubViewport(false);

    const actions = ref<ActionItems>([...ACTIONS]);
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () => h(SecondaryNav, {items: [], actions: actions.value}),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    actions.value = [...ACTIONS, {label: 'New Site', onClick: () => {}}];
    await nextTick();

    // `craft-popover` moves unslotted children into a wrapper it makes once,
    // so without a wrapper of our own the new item renders into the host
    // instead and never reaches the slot — visible nowhere, and silent.
    const menu = container.querySelector('craft-action-menu')!;
    const content = menu.querySelector(":scope > [slot='content']")!;

    expect(menu.querySelectorAll(":scope > [slot='content']")).toHaveLength(1);
    expect(
      [...content.querySelectorAll('craft-action-item')].map((item) =>
        item.textContent?.trim()
      )
    ).toEqual(['New Group', 'New Site']);
  });

  it('moves the same actions into the menu once the nav collapses', async () => {
    stubViewport(false);

    const container = mount();
    await nextTick();

    const menu = container.querySelector('craft-action-menu')!;
    const items = [...menu.querySelectorAll('craft-action-item')];

    // Appended after the nav's own items, behind a separator — the page
    // described these once and didn't write either rendering.
    expect(items.at(-1)?.textContent).toContain('New Group');
    expect(menu.querySelector('hr')).not.toBeNull();
    // Commands alone are not a choice, so nothing is guttered here.
    expect(
      items.every(
        (item) => (item as HTMLElement & {type?: string}).type === 'button'
      )
    ).toBe(true);
    expect(container.querySelector('.secondary-nav__actions')).toBeNull();
  });
});
