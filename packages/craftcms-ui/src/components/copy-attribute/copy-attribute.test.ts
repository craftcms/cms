import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './copy-attribute.js';
import type CraftCopyAttribute from './copy-attribute.js';

async function createCopyAttribute(
  attrs: Record<string, string> = {}
): Promise<CraftCopyAttribute> {
  const element = document.createElement(
    'craft-copy-attribute'
  ) as CraftCopyAttribute;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function inner(element: CraftCopyAttribute): HTMLElement {
  return element.shadowRoot!.querySelector('craft-copy-button')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-copy-attribute', () => {
  /** It is a copy button that shows the value it copies. */
  it('shows the value and hands it to the copy button', async () => {
    const element = await createCopyAttribute({value: 'fieldHandle'});

    expect(inner(element).getAttribute('value')).toBe('fieldHandle');
    expect(inner(element).textContent?.trim()).toBe('fieldHandle');
  });

  it('follows a changed value', async () => {
    const element = await createCopyAttribute({value: 'one'});

    element.value = 'two';
    await element.updateComplete;

    expect(inner(element).getAttribute('value')).toBe('two');
  });

  it('gives each instance an id, so its tooltip can be referenced', async () => {
    const first = await createCopyAttribute({value: 'a'});
    const second = await createCopyAttribute({value: 'b'});

    expect(inner(first).id).not.toBe('');
    expect(inner(first).id).not.toBe(inner(second).id);
  });
});
