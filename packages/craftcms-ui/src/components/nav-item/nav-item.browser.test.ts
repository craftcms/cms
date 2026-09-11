import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftNavItem from './nav-item.js';
import './nav-item.js';
import '../nav-list/nav-list.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

/** A collapsed item with a subnav, so it renders both an icon and a chevron. */
async function railFixture(): Promise<CraftNavItem> {
  const list = document.createElement('craft-nav-list');
  const item = document.createElement('craft-nav-item') as CraftNavItem;
  item.setAttribute('icon', 'gear');
  item.setAttribute('href', '/admin/graphql');
  item.setAttribute('icon-only', '');
  item.append(document.createTextNode('GraphQL'));

  const subnav = document.createElement('craft-nav-list');
  subnav.slot = 'subnav';
  const child = document.createElement('craft-nav-item');
  child.setAttribute('href', '/admin/graphql/schemas');
  child.textContent = 'Schemas';
  subnav.append(child);
  item.append(subnav);

  list.append(item);
  document.body.append(list);
  await item.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(() => resolve(null)));
  return item;
}

/**
 * The box `craft-icon` gives itself, which is what decides how big the glyph
 * inside it comes out — measured rather than the svg, which is fetched and so
 * may never arrive in a test.
 */
function iconBox(root: ParentNode, selector: string) {
  const icon = root.querySelector(selector)!;
  const {width, height} = icon.getBoundingClientRect();

  return {width, height, fontSize: getComputedStyle(icon).fontSize};
}

it('lines the collapsed chevron up with the icon it replaces', async () => {
  const item = await railFixture();
  const shadow = item.shadowRoot!;
  const centre = (rect: DOMRect) => [
    Math.round(rect.x + rect.width / 2),
    Math.round(rect.y + rect.height / 2),
  ];

  const row = shadow.querySelector('.nav-item--icon')!.getBoundingClientRect();
  const toggle = shadow
    .querySelector('.rail-toggle craft-button')!
    .getBoundingClientRect();

  // The chevron takes the icon's place on focus, and the focus ring is drawn
  // on the button — so a box that doesn't match the row's makes the swap read
  // as the row resizing. The row's box includes a transparent border, which is
  // what the two used to differ by.
  expect(centre(toggle)).toEqual(centre(row));
  expect(toggle.width).toBeCloseTo(row.width, 1);
  expect(toggle.height).toBeCloseTo(row.height, 1);
});

it('draws the same focus ring on the row and on its chevron', async () => {
  const item = await railFixture();
  const shadow = item.shadowRoot!;
  const row = shadow.querySelector<HTMLElement>('.nav-item--icon')!;
  const toggle = shadow.querySelector<HTMLElement>(
    '.rail-toggle craft-button'
  )!;

  const ring = (el: HTMLElement) => {
    el.focus();
    const {outlineWidth, outlineOffset, outlineColor} = getComputedStyle(el);
    return {outlineWidth, outlineOffset, outlineColor};
  };

  const rowRing = ring(row);

  // Not the browser's default: the row is the focusable element when
  // collapsed, and nothing in the stylesheet used to match it — so it fell
  // through to a ring of a different width and offset than craft-button's.
  expect(rowRing.outlineWidth).toBe('2px');
  expect(ring(toggle)).toEqual(rowRing);
});

it('draws the chevron at the same size as the icon it replaces', async () => {
  const item = await railFixture();
  const shadow = item.shadowRoot!;

  const icon = iconBox(shadow, 'craft-icon.nav-icon');
  const chevron = iconBox(shadow, '.rail-toggle craft-icon');

  // Both are sized off their own font-size, and craft-button shrinks whatever
  // it contains — so this guards the em chain as much as the final number.
  expect(icon.height).toBeGreaterThan(0);
  expect(chevron.fontSize).toBe(icon.fontSize);
  expect(chevron.height).toBeCloseTo(icon.height, 1);
  expect(chevron.width).toBeCloseTo(icon.width, 1);
});
