import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftBreadcrumbItem from './breadcrumb-item.js';
import './breadcrumb-item.js';

async function createItem(
  attrs: Record<string, string> = {},
  text = 'Entries'
): Promise<CraftBreadcrumbItem> {
  const element = document.createElement(
    'craft-breadcrumb-item'
  ) as CraftBreadcrumbItem;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.textContent = text;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-breadcrumb-item', () => {
  it('renders a span label without href', async () => {
    const element = await createItem();
    expect(element.shadowRoot!.querySelector('a')).toBeNull();
    expect(element.shadowRoot!.querySelector('span.label')).not.toBeNull();
  });

  it('renders a link when href is set', async () => {
    const element = await createItem({href: '/entries'});
    const link = element.shadowRoot!.querySelector('a.label');
    expect(link).not.toBeNull();
    expect(link!.getAttribute('href')).toBe('/entries');
  });

  it('opens a link to another host in a new tab and says so', async () => {
    const element = await createItem({href: 'https://craftcms.com/'});
    const link = element.shadowRoot!.querySelector('a.label')!;
    expect(link.getAttribute('target')).toBe('_blank');
    expect(link.getAttribute('rel')).toContain('noopener');
    expect(link.textContent).toContain('Opens in a new window');
  });

  it('opens a link to this host in the same tab', async () => {
    const element = await createItem({href: '/entries'});
    const link = element.shadowRoot!.querySelector('a.label')!;
    expect(link.hasAttribute('target')).toBe(false);
    expect(link.textContent).not.toContain('Opens in a new window');
  });

  it('exposes href as a property', async () => {
    const element = await createItem({href: '/entries'});
    expect(element.href).toBe('/entries');
  });

  it('has a separator slot for breadcrumbs to fill', async () => {
    const element = await createItem();
    const separator = document.createElement('span');
    separator.slot = 'separator';
    separator.textContent = '/';
    element.append(separator);
    await element.updateComplete;

    const slot = element.shadowRoot!.querySelector<HTMLSlotElement>(
      'slot[name="separator"]'
    );
    expect(slot).not.toBeNull();
    expect(slot!.assignedElements()).toEqual([separator]);
  });
});
