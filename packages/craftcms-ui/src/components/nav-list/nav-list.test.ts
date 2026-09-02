import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './nav-list.js';
import '../nav-item/nav-item.js';
import type CraftNavList from './nav-list.js';

async function createNavList(innerHTML = ''): Promise<CraftNavList> {
  const element = document.createElement('craft-nav-list') as CraftNavList;
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-nav-list', () => {
  /**
   * The list element is what makes a run of nav items a list to a screen
   * reader, which is how it announces how many there are and where you are in
   * them.
   */
  it('renders a list around its items', async () => {
    const element = await createNavList(
      '<craft-nav-item>Entries</craft-nav-item>'
    );

    expect(element.shadowRoot!.querySelector('ul')).toBeTruthy();
    expect(element.shadowRoot!.querySelector('slot')).toBeTruthy();
  });

  it('takes its items from the default slot', async () => {
    const element = await createNavList(`
      <craft-nav-item>Entries</craft-nav-item>
      <craft-nav-item>Assets</craft-nav-item>
    `);

    const slot = element.shadowRoot!.querySelector('slot')!;

    expect(
      slot.assignedElements().map((item) => item.tagName.toLowerCase())
    ).toEqual(['craft-nav-item', 'craft-nav-item']);
  });

  it('renders an empty list rather than nothing', async () => {
    const element = await createNavList();

    expect(element.shadowRoot!.querySelector('ul')).toBeTruthy();
  });
});
