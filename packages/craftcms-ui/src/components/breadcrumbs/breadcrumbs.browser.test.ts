import {beforeEach, describe, expect, it} from 'vite-plus/test';
import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
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
  const menuLabels = () =>
    overflow.hidden
      ? []
      : (menu.actions as ActionMenuItem[]).map((action) =>
          'label' in action ? action.label : ''
        );

  return {container, element, items, menu, overflow, shown, menuLabels};
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
    const {items, menu} = await trail('120px');
    const link = items[1]!.shadowRoot!.querySelector('a')!;
    let followed = false;
    link.addEventListener('click', (event) => {
      event.preventDefault();
      followed = true;
    });

    const action = (menu.actions as ActionMenuItem[])[1] as {
      href: string;
      onClick: (event: Event) => void;
    };
    expect(action.href).toBe(link.href);

    action.onClick(new MouseEvent('click', {cancelable: true}));
    expect(followed).toBe(true);
  });
});
