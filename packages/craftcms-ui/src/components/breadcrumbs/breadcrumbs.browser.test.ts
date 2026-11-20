import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';
import type CraftBreadcrumbs from './breadcrumbs.js';
import type CraftActionMenu from '../action-menu/action-menu.js';
import type {ActionMenuItem} from '../action-menu/action-menu.types.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

const frame = () => new Promise((resolve) => requestAnimationFrame(resolve));

const TRAIL = `
  <craft-breadcrumb-item href="#root">Site Name</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#entries">Entries</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#type">Entry Type</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#parent">Parent Entry</craft-breadcrumb-item>
  <craft-breadcrumb-item>Current Entry With A Long Title</craft-breadcrumb-item>`;

async function trail(width: string, innerHTML = TRAIL) {
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  const container = document.createElement('div');
  container.style.inlineSize = width;
  const element = document.createElement(
    'craft-breadcrumbs'
  ) as CraftBreadcrumbs;
  element.innerHTML = innerHTML;
  container.append(element);
  document.body.append(container);
  await element.updateComplete;
  await frame();
  await frame();

  const items = [...element.querySelectorAll('craft-breadcrumb-item')];
  const menu =
    element.shadowRoot!.querySelector<CraftActionMenu>('.breadcrumbs__menu')!;
  const overflow = menu.closest('craft-breadcrumb-item')!;
  const shown = () =>
    items
      .filter((item) => !item.hidden)
      .map((item) => item.firstChild!.textContent!.trim());
  // The menu reads its items when it opens.
  const actions = () => {
    const value = menu.actions;
    return typeof value === 'function' ? value() : (value ?? []);
  };
  const menuLabels = () =>
    overflow.hidden
      ? []
      : actions().map((action) => ('label' in action ? action.label : ''));

  return {
    container,
    element,
    items,
    menu,
    overflow,
    shown,
    menuLabels,
    actions,
  };
}

describe('craft-breadcrumbs', () => {
  it('centers each separator between the crumbs on either side of it', async () => {
    const {items} = await trail(
      '100%',
      `
      <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
      <craft-breadcrumb-item href="#">News</craft-breadcrumb-item>
      <craft-breadcrumb-item>Launch</craft-breadcrumb-item>`
    );

    const label = (item: Element) =>
      item
        .shadowRoot!.querySelector('[part~="label"]')!
        .getBoundingClientRect();

    for (const [index, item] of items.slice(0, -1).entries()) {
      const separator = item
        .querySelector('[slot="separator"]')!
        .getBoundingClientRect();
      const before = separator.left - label(item).right;
      const after = label(items[index + 1]!).left - separator.right;

      expect(before).toBeGreaterThan(0);
      expect(before).toBeCloseTo(after, 1);
    }
  });

  it('shows every crumb, and no menu, when the trail fits', async () => {
    const {items, overflow, shown} = await trail('2000px');

    expect(shown()).toHaveLength(items.length);
    expect(overflow.hidden).toBe(true);
  });

  it('collapses from the middle, keeping the first and last crumbs', async () => {
    const {element, items, overflow, shown, menuLabels} = await trail('400px');

    expect(shown()[0]).toBe('Site Name');
    expect(shown().at(-1)).toBe('Current Entry With A Long Title');
    expect(shown().length).toBeLessThan(items.length);
    // What collapsed is in the menu, in trail order.
    expect(menuLabels()).toEqual(
      ['Entries', 'Entry Type', 'Parent Entry'].filter(
        (label) => !shown().includes(label)
      )
    );

    // The menu sits between the first crumb and the rest.
    const first = items[0]!.getBoundingClientRect();
    const menu = overflow.getBoundingClientRect();
    const next = items.find((item, i) => i > 0 && !item.hidden)!;
    expect(menu.left).toBeGreaterThanOrEqual(first.right);
    expect(next.getBoundingClientRect().left).toBeGreaterThanOrEqual(
      menu.right
    );
    expect(element.scrollWidth).toBeLessThanOrEqual(element.clientWidth + 1);
  });

  it('keeps the nearest ancestors before the ones further up', async () => {
    const {shown} = await trail('520px');

    const kept = shown().slice(1, -1);
    const middle = ['Entries', 'Entry Type', 'Parent Entry'];
    expect(kept).toEqual(middle.slice(middle.length - kept.length));
  });

  it('truncates the last crumb when nothing else fits beside it', async () => {
    const {element, items, shown, menuLabels} = await trail('120px');

    expect(shown()).toEqual(['Current Entry With A Long Title']);
    expect(menuLabels()).toEqual([
      'Site Name',
      'Entries',
      'Entry Type',
      'Parent Entry',
    ]);

    const label = items
      .at(-1)!
      .shadowRoot!.querySelector<HTMLElement>('[part~="label"]')!;
    expect(label.scrollWidth).toBeGreaterThan(label.clientWidth);
    expect(element.getBoundingClientRect().right).toBeLessThanOrEqual(121);
  });

  it('brings crumbs back as the trail is given room', async () => {
    const {container, items, overflow, shown} = await trail('200px');
    expect(overflow.hidden).toBe(false);

    container.style.inlineSize = '2000px';
    await frame();
    await frame();

    expect(shown()).toHaveLength(items.length);
    expect(overflow.hidden).toBe(true);
  });

  it('follows the collapsed crumb’s own link from the menu', async () => {
    const {items, actions} = await trail('120px');
    const link = items[1]!.shadowRoot!.querySelector('a')!;
    let followed = false;
    link.addEventListener('click', (event) => {
      event.preventDefault();
      followed = true;
    });

    const action = actions()[1] as {
      href: string;
      onClick: (event: Event) => void;
    };
    expect(action.href).toBe(link.href);

    action.onClick(new MouseEvent('click', {cancelable: true}));
    expect(followed).toBe(true);
  });

  it('keeps a collapsed crumb’s own menu, linked or not, in the overflow menu', async () => {
    const {actions, shown} = await trail(
      '120px',
      `
      <craft-breadcrumb-item>English<craft-action-menu slot="suffix">
        <craft-action-item checked>English</craft-action-item>
        <craft-action-item href="#french">French</craft-action-item>
      </craft-action-menu></craft-breadcrumb-item>
      <craft-breadcrumb-item href="#singles">Singles<craft-action-menu slot="suffix">
        <craft-action-item href="#singles" checked>Singles</craft-action-item>
        <craft-action-item href="#channels">Channels</craft-action-item>
        <craft-action-item href="#structures" hidden>Structures</craft-action-item>
      </craft-action-menu></craft-breadcrumb-item>
      <craft-breadcrumb-item>Current Entry With A Long Title</craft-breadcrumb-item>`
    );
    expect(shown()).toEqual(['Current Entry With A Long Title']);

    const [site, section] = actions() as Array<{
      type: string;
      heading: string;
      items: Array<{label: string; href?: string; current?: boolean}>;
    }>;
    expect(site).toMatchObject({type: 'group', heading: 'English'});
    expect(site!.items.map(({label, current}) => [label, current])).toEqual([
      ['English', true],
      ['French', undefined],
    ]);
    expect(section).toMatchObject({type: 'group', heading: 'Singles'});
    // Listed once: the switcher already holds the crumb's own page. Its hidden
    // choice stays hidden.
    expect(section!.items.map(({label, current}) => [label, current])).toEqual([
      ['Singles', true],
      ['Channels', undefined],
    ]);

    // Choosing one presses the item it stands in for.
    const channels = document.querySelector<HTMLElement>(
      'craft-action-item[href="#channels"]'
    )!;
    let pressed = false;
    channels.addEventListener('click', (event) => {
      event.preventDefault();
      pressed = true;
    });
    const choice = section!.items[1] as unknown as {
      onClick: (event: Event) => void;
    };
    choice.onClick(new MouseEvent('click', {cancelable: true}));
    expect(pressed).toBe(true);
  });

  it('follows a collapsed crumb’s plain menu link when it’s chosen', async () => {
    history.replaceState(null, '', '#start');
    const {element} = await trail(
      '120px',
      `
      <craft-breadcrumb-item href="#singles">Singles<craft-action-menu slot="suffix">
        <craft-action-item href="#channels">Channels</craft-action-item>
      </craft-action-menu></craft-breadcrumb-item>
      <craft-breadcrumb-item>Current Entry With A Long Title</craft-breadcrumb-item>`
    );

    const overflowMenu =
      element.shadowRoot!.querySelector('.breadcrumbs__menu')!;
    await userEvent.click(overflowMenu.querySelector('[slot="invoker"]')!);
    await frame();
    const choice = [
      ...overflowMenu.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent!.trim() === 'Channels')!;
    await userEvent.click(choice);
    await frame();

    expect(location.hash).toBe('#channels');
    history.replaceState(null, '', location.pathname + location.search);
  });

  it('puts the menu between the first crumb and the rest in focus order', async () => {
    const {element, items, shown} = await trail('520px');
    // Partly collapsed: the menu and at least one middle crumb are visible.
    expect(shown().length).toBeGreaterThan(2);
    expect(shown().length).toBeLessThan(items.length);

    const focused = () =>
      document.activeElement === element
        ? 'menu'
        : document.activeElement!.firstChild!.textContent!.trim();
    // The last crumb has no link, so it takes no focus.
    const expected = ['Site Name', 'menu', ...shown().slice(1, -1)];

    const forward = [];
    for (const _ of expected) {
      await userEvent.tab();
      forward.push(focused());
    }
    expect(forward).toEqual(expected);

    const backward = [focused()];
    for (const _ of expected.slice(1)) {
      await userEvent.tab({shift: true});
      backward.push(focused());
    }
    expect(backward).toEqual([...expected].reverse());
  });

  it('keeps the menu and truncation in place around a custom separator', async () => {
    const separator = '<span slot="separator">/</span>';
    const {element, items, overflow} = await trail(
      '120px',
      separator + TRAIL + separator
    );

    const label = items
      .at(-1)!
      .shadowRoot!.querySelector<HTMLElement>('[part~="label"]')!;
    expect(label.scrollWidth).toBeGreaterThan(label.clientWidth);
    expect(element.getBoundingClientRect().right).toBeLessThanOrEqual(121);
    expect(overflow.getBoundingClientRect().right).toBeLessThanOrEqual(
      items.at(-1)!.getBoundingClientRect().left
    );
  });

  it('follows a crumb replaced at the same place, with the same label', async () => {
    const {element, items, actions} = await trail('120px');
    const replacement = document.createElement('craft-breadcrumb-item');
    replacement.href = '#elsewhere';
    replacement.textContent = 'Entries';
    items[1]!.replaceWith(replacement);
    await replacement.updateComplete;
    await frame();
    await frame();

    const link = replacement.shadowRoot!.querySelector('a')!;
    let followed = false;
    link.addEventListener('click', (event) => {
      event.preventDefault();
      followed = true;
    });

    const action = actions()[1] as {
      href: string;
      onClick: (event: Event) => void;
    };
    expect(element.contains(replacement)).toBe(true);
    expect(action.href).toBe(link.href);
    action.onClick(new MouseEvent('click', {cancelable: true}));
    expect(followed).toBe(true);
  });

  it('remeasures when a crumb’s label or link changes in place', async () => {
    const {items, overflow, shown, menuLabels, actions} = await trail('400px');
    // Let any measurement still queued from the first layout run first.
    await frame();
    await frame();
    const collapsed = items.find((item) => item.hidden)!;

    collapsed.firstChild!.textContent = 'Renamed';
    collapsed.setAttribute('href', '#renamed');
    await frame();
    await frame();
    expect(menuLabels()).toContain('Renamed');
    expect(
      actions().map((action) => ('href' in action ? String(action.href) : ''))
    ).toContainEqual(expect.stringMatching(/#renamed$/));

    // Short labels all fit at the same width.
    items.forEach((item, index) => {
      item.firstChild!.textContent = `C${index}`;
    });
    await frame();
    await frame();
    expect(shown()).toHaveLength(items.length);
    expect(overflow.hidden).toBe(true);
  });
});
