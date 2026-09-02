import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './select.js';
import type CraftSelect from './select.js';

const OPTIONS = `
  <label slot="label">Language</label>
  <select slot="input">
    <option value="">Select a language</option>
    <option value="fr-FR">French</option>
    <option value="en-US">English</option>
  </select>
`;

async function createSelect(
  attrs: Record<string, string> = {},
  innerHTML = OPTIONS
): Promise<CraftSelect> {
  const element = document.createElement('craft-select') as CraftSelect;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

function native(element: CraftSelect): HTMLSelectElement {
  return element.querySelector('select')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-select', () => {
  /**
   * The native `<select>` is slotted rather than rendered, so the component
   * adds the field chrome around whatever the server or the page already put
   * there.
   */
  it('adopts the slotted select', async () => {
    const element = await createSelect();

    expect(native(element)).toBeTruthy();
    expect(native(element).options).toHaveLength(3);
  });

  it('wires the label to the control', async () => {
    const element = await createSelect();

    expect(element.querySelector('label')!.getAttribute('for')).toBe(
      native(element).id
    );
  });

  it('reads the selection as its model value', async () => {
    const element = await createSelect();

    native(element).value = 'en-US';
    native(element).dispatchEvent(new Event('change', {bubbles: true}));
    await element.updateComplete;

    expect(element.modelValue).toBe('en-US');
  });

  it('reflects small so the stylesheet can act on it', async () => {
    const element = await createSelect({small: ''});

    expect(element.hasAttribute('small')).toBe(true);
  });

  it('disables the native control', async () => {
    const element = await createSelect({disabled: ''});

    expect(native(element).disabled).toBe(true);
  });
});
