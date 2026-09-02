import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './option.js';
import type CraftOption from './option.js';

async function createOption(
  attrs: Record<string, string> = {},
  innerHTML = 'Blog'
): Promise<CraftOption> {
  const element = document.createElement('craft-option') as CraftOption;
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

describe('craft-option', () => {
  /** The label is slotted, which is what lets an option hold markup. */
  it('renders its label from the default slot', async () => {
    const element = await createOption();

    expect(element.shadowRoot!.querySelector('slot:not([name])')).toBeTruthy();
    expect(element.textContent).toBe('Blog');
  });

  it('renders no hint unless one is set', async () => {
    const element = await createOption();

    expect(element.shadowRoot!.querySelector('.hint')).toBeNull();
  });

  it('renders a hint after the label', async () => {
    const element = await createOption({hint: '12 entries'});

    expect(
      element.shadowRoot!.querySelector('.hint')?.textContent?.trim()
    ).toBe('12 entries');
  });

  it('offers a suffix slot for trailing content', async () => {
    const element = await createOption();

    expect(
      element.shadowRoot!.querySelector('slot[name="suffix"]')
    ).toBeTruthy();
  });

  /**
   * The value is Lion's `choiceValue`, a property rather than an attribute, so
   * an option can carry a value that is not a string.
   */
  it('carries its value as choiceValue', async () => {
    const element = await createOption();

    element.choiceValue = 'blog';

    expect(element.choiceValue).toBe('blog');
  });
});
