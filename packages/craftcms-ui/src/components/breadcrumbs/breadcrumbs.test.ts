import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
import type CraftBreadcrumbs from './breadcrumbs.js';

async function createBreadcrumbs(
  attrs: Record<string, string> = {},
  innerHTML = `
    <craft-breadcrumb-item href="#">Site</craft-breadcrumb-item>
    <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
    <craft-breadcrumb-item href="#">News</craft-breadcrumb-item>
  `
): Promise<CraftBreadcrumbs> {
  const element = document.createElement(
    'craft-breadcrumbs'
  ) as CraftBreadcrumbs;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function nav(element: CraftBreadcrumbs): HTMLElement {
  return element.shadowRoot!.querySelector('nav')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-breadcrumbs', () => {
  /**
   * A page can hold more than one navigation landmark, so the trail names
   * itself rather than being announced as an unlabelled "navigation".
   */
  it('is a named navigation landmark', async () => {
    const element = await createBreadcrumbs();

    expect(nav(element)).toBeTruthy();
    expect(nav(element).getAttribute('aria-label')).toBeTruthy();
  });

  it('takes a label of its own', async () => {
    const element = await createBreadcrumbs({label: 'You are here'});

    expect(nav(element).getAttribute('aria-label')).toBe('You are here');
  });

  it('renders its items from the default slot', async () => {
    const element = await createBreadcrumbs();
    const slot =
      element.shadowRoot!.querySelector<HTMLSlotElement>('slot:not([name])')!;

    expect(slot.assignedElements()).toHaveLength(3);
  });

  /**
   * The separator is drawn between items and carries no meaning of its own, so
   * the template it is cloned from is hidden from assistive technology.
   */
  it('keeps the separator out of the accessibility tree', async () => {
    const element = await createBreadcrumbs();
    const separator = element.shadowRoot!.querySelector(
      'slot[name="separator"]'
    )!.parentElement!;

    expect(separator.getAttribute('aria-hidden')).toBe('true');
    expect(separator.hasAttribute('hidden')).toBe(true);
  });
});
