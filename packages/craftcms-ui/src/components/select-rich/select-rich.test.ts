import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './select-rich.js';
import type CraftSelectRich from './select-rich.js';

async function createSelectRich(
  attrs: Record<string, string> = {}
): Promise<CraftSelectRich> {
  const element = document.createElement(
    'craft-select-rich'
  ) as CraftSelectRich;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = '<label slot="label">Favorite Fruit</label>';
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-select-rich', () => {
  it('upgrades and renders its field chrome', async () => {
    const element = await createSelectRich();

    expect(element.shadowRoot).toBeTruthy();
    expect(element.querySelector('label[slot="label"]')).toBeTruthy();
  });

  it('leaves keyboard shortcuts out of type-ahead', async () => {
    const element = await createSelectRich();
    element.insertAdjacentHTML(
      'beforeend',
      '<craft-option value="apple">Apple</craft-option>' +
        '<craft-option value="strawberry">Strawberry</craft-option>'
    );
    await new Promise((resolve) => setTimeout(resolve));
    const invoker = element.querySelector('craft-select-invoker')!;
    const press = (init: KeyboardEventInit) =>
      invoker.dispatchEvent(
        new KeyboardEvent('keydown', {bubbles: true, ...init})
      );

    // ⌘S while the select has focus must not jump to an "S" option.
    press({key: 's', metaKey: true});
    press({key: 's', ctrlKey: true});
    expect(element.value).toBe('apple');

    press({key: 's'});
    expect(element.value).toBe('strawberry');
  });

  it('takes a name for posting', async () => {
    const element = await createSelectRich({name: 'fruit'});

    expect(element.name).toBe('fruit');
  });

  it('reflects small so the stylesheet can act on it', async () => {
    const element = await createSelectRich({small: ''});

    expect(element.hasAttribute('small')).toBe(true);
  });
});
