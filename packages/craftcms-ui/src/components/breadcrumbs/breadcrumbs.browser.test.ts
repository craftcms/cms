import {beforeEach, describe, expect, it} from 'vite-plus/test';
import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
import type CraftBreadcrumbs from './breadcrumbs.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-breadcrumbs', () => {
  it('centers each separator between the crumbs on either side of it', async () => {
    await import('../../styles/shared/variables.css');
    await import('../../styles/shared/tokens.css');

    const element = document.createElement(
      'craft-breadcrumbs'
    ) as CraftBreadcrumbs;
    element.innerHTML = `
      <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
      <craft-breadcrumb-item href="#">News</craft-breadcrumb-item>
      <craft-breadcrumb-item>Launch</craft-breadcrumb-item>`;
    document.body.append(element);
    await element.updateComplete;
    // Separators are added once the items are slotted.
    await new Promise((resolve) => requestAnimationFrame(resolve));

    const items = [...element.querySelectorAll('craft-breadcrumb-item')];
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
});
