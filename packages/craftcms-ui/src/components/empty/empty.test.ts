import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './empty.js';
import type CraftEmpty from './empty.js';

async function createEmpty(
  attrs: Record<string, string> = {},
  innerHTML = ''
): Promise<CraftEmpty> {
  const element = document.createElement('craft-empty') as CraftEmpty;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-empty', () => {
  it('renders the label', async () => {
    const element = await createEmpty({label: 'Nothing yet.'});

    expect(
      element.shadowRoot!.querySelector('.label')?.textContent?.trim()
    ).toBe('Nothing yet.');
  });

  it('renders an icon when one is named', async () => {
    const element = await createEmpty({
      label: 'None',
      icon: 'magnifying-glass',
    });

    expect(
      element.shadowRoot!.querySelector('craft-icon')?.getAttribute('name')
    ).toBe('magnifying-glass');
  });

  it('renders no icon when none is named', async () => {
    const element = await createEmpty({label: 'None'});

    expect(element.shadowRoot!.querySelector('craft-icon')).toBeNull();
  });

  /** Each slot is a fallback point, so slotting replaces what it defaults to. */
  it('offers graphic, content, and default slots', async () => {
    const element = await createEmpty({label: 'None'});
    const names = [...element.shadowRoot!.querySelectorAll('slot')].map(
      (slot) => slot.getAttribute('name')
    );

    expect(names).toEqual(expect.arrayContaining(['graphic', 'content', null]));
  });
});
