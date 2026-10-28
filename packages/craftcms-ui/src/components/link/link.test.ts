import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './link.js';
import type CraftButton from '../button/button.js';
import type CraftLink from './link.js';

async function createLink(
  anchor = '<a href="https://www.imdb.com/title/tt1674773/">www.imdb.com/title/tt1674773/</a>',
  attrs: Record<string, string> = {}
): Promise<CraftLink> {
  const element = document.createElement('craft-link') as CraftLink;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = anchor;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function query<T extends Element = Element>(
  element: CraftLink,
  selector: string
): T | null {
  return element.shadowRoot!.querySelector<T>(selector);
}

function copyValue(element: CraftLink): string | undefined {
  const copy = query<CraftButton>(element, '.details__copy')!;

  return (copy.action as {value?: string} | null)?.value;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-link', () => {
  it('keeps the link in the light DOM', async () => {
    const element = await createLink();

    expect(element.querySelector('a')!.getAttribute('href')).toBe(
      'https://www.imdb.com/title/tt1674773/'
    );
    expect(query(element, '.text slot')).toBeTruthy();
  });

  it('opens the details from a button', async () => {
    const element = await createLink();
    const invoker = query(
      element,
      'craft-popover > craft-button[slot="invoker"]'
    );

    expect(invoker).toBeTruthy();
    expect(invoker!.getAttribute('type')).toBe('button');
  });

  it('names the details button after the link', async () => {
    const element = await createLink();

    expect(
      query(element, '[slot="invoker"] craft-icon')!.getAttribute('label')
    ).toBe('Link details www.imdb.com/title/tt1674773/');
  });

  it('takes a label of its own', async () => {
    const element = await createLink(undefined, {label: 'About this link'});

    expect(
      query(element, '[slot="invoker"] craft-icon')!.getAttribute('label')
    ).toBe('About this link www.imdb.com/title/tt1674773/');
  });

  it('marks the details button with the kind of link', async () => {
    const element = await createLink();

    expect(
      query(element, '[slot="invoker"] craft-icon')!.getAttribute('name')
    ).toBe('link');
  });

  it('names the details after the kind of link', async () => {
    const element = await createLink();

    expect(query(element, 'craft-popover')!.getAttribute('label')).toBe('Link');
    expect(query(element, '.details__heading')!.textContent).toBe('Link');
  });

  it('shows and copies the full address', async () => {
    const element = await createLink();

    expect(query(element, '.details__address')!.textContent).toBe(
      'https://www.imdb.com/title/tt1674773/'
    );
    expect(copyValue(element)).toBe('https://www.imdb.com/title/tt1674773/');
  });

  it('heads the details with text alone', async () => {
    const element = await createLink();

    expect(query(element, '.details__header > craft-icon')).toBeNull();
  });

  it('links the full address to the same place as the original', async () => {
    const element = await createLink(
      '<a href="https://craftcms.com/" target="_blank" rel="noopener">craftcms.com</a>'
    );
    const address = query<HTMLAnchorElement>(element, 'a.details__address')!;

    expect(address.getAttribute('href')).toBe('https://craftcms.com/');
    expect(address.getAttribute('target')).toBe('_blank');
    expect(address.getAttribute('rel')).toBe('noopener');
  });

  it('styles the copy button as the open button', async () => {
    const element = await createLink();
    const open = query(element, '.details__open')!;
    const copy = query(element, '.details__copy')!;

    expect(copy.localName).toBe(open.localName);
    for (const attribute of ['variant', 'size', 'icon']) {
      expect(copy.getAttribute(attribute)).toBe(open.getAttribute(attribute));
    }
    expect(copy.querySelector('craft-icon')!.getAttribute('label')).toBe(
      'Copy'
    );
  });

  it('opens the address in a new tab, even when the link opens in place', async () => {
    const element = await createLink(
      '<a href="https://craftcms.com/" rel="noopener">craftcms.com</a>'
    );
    const open = query(element, '.details__open')!;

    expect(open.getAttribute('href')).toBe('https://craftcms.com/');
    expect(open.getAttribute('target')).toBe('_blank');
    expect(open.getAttribute('rel')).toBe('noopener');
    expect(open.querySelector('craft-icon')!.getAttribute('label')).toBe(
      'Open in a new tab'
    );
  });

  it.each([
    ['mailto:someone%40example.com?subject=Hi', 'Email', 'someone@example.com'],
    ['tel:+15555550123', 'Phone', '+15555550123'],
  ])(
    'shows and copies the bare address of %s',
    async (href, heading, address) => {
      const element = await createLink(`<a href="${href}">${address}</a>`);

      expect(query(element, '.details__heading')!.textContent).toBe(heading);
      expect(query(element, '.details__address')!.textContent).toBe(address);
      expect(copyValue(element)).toBe(address);
    }
  );

  it('follows changes to the link', async () => {
    const element = await createLink();
    element.querySelector('a')!.setAttribute('href', 'https://craftcms.com/');
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;

    expect(query(element, '.details__address')!.textContent).toBe(
      'https://craftcms.com/'
    );
  });

  it('offers no details without a link', async () => {
    const element = await createLink('Just text');

    expect(query(element, 'craft-popover')).toBeNull();
  });
});
