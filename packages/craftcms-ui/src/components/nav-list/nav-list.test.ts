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

  it('lays its items out as a list by default, marking none of them', async () => {
    const element = await createNavList(
      '<craft-nav-item>Entries</craft-nav-item>'
    );

    expect(element.appearance).toBe('list');
    expect(
      element.querySelector('craft-nav-item')!.hasAttribute('appearance')
    ).toBe(false);
  });

  it('passes a tabs appearance down to its items', async () => {
    const element = await createNavList(`
      <craft-nav-item>Feeds</craft-nav-item>
      <craft-nav-item>Logs</craft-nav-item>
    `);

    element.appearance = 'tabs';
    await element.updateComplete;

    expect(
      [...element.querySelectorAll('craft-nav-item')].map((item) =>
        item.getAttribute('appearance')
      )
    ).toEqual(['tabs', 'tabs']);
  });

  it('passes the appearance to an item added after it rendered', async () => {
    const element = await createNavList(
      '<craft-nav-item>Feeds</craft-nav-item>'
    );
    element.appearance = 'tabs';
    await element.updateComplete;

    const added = document.createElement('craft-nav-item');
    added.textContent = 'Logs';
    element.append(added);
    await new Promise((resolve) => setTimeout(resolve));

    expect(added.getAttribute('appearance')).toBe('tabs');
  });

  it('takes the appearance back off its items when it returns to a list', async () => {
    const element = await createNavList(
      '<craft-nav-item>Feeds</craft-nav-item>'
    );
    element.appearance = 'tabs';
    await element.updateComplete;

    element.appearance = 'list';
    await element.updateComplete;

    expect(
      element.querySelector('craft-nav-item')!.hasAttribute('appearance')
    ).toBe(false);
  });

  it('keeps its list semantics as a tab strip', async () => {
    const element = await createNavList(`
      <craft-nav-item href="/feeds">Feeds</craft-nav-item>
      <craft-nav-item href="/logs">Logs</craft-nav-item>
    `);

    element.appearance = 'tabs';
    await element.updateComplete;

    const list = element.shadowRoot!.querySelector('ul')!;

    expect(list).toBeTruthy();
    expect(list.getAttribute('role')).toBe(null);
    expect(element.shadowRoot!.querySelector('[role="tablist"]')).toBe(null);
    expect(
      [...element.querySelectorAll('craft-nav-item')].map((item) =>
        item.getAttribute('role')
      )
    ).toEqual(['listitem', 'listitem']);
  });
});
