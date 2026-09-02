import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './input.js';
import type CraftInput from './input.js';

async function createInput(
  attrs: Record<string, string> = {},
  innerHTML = '<label slot="label">Handle</label>'
): Promise<CraftInput> {
  const element = document.createElement('craft-input') as CraftInput;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  // Lion wires the label and feedback relations after its own first update,
  // so give it a turn before reading them.
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

/** Lion's native input, which is the element the component drives. */
function native(element: CraftInput): HTMLInputElement {
  return element.querySelector('input')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input', () => {
  it('renders a native input with the label wired to it', async () => {
    const element = await createInput();
    const label = element.querySelector('label')!;

    expect(native(element)).toBeTruthy();
    // Lion rewrites the label's `for` to the id it generated for the input,
    // which is what makes clicking the label focus the field.
    expect(label.getAttribute('for')).toBe(native(element).id);
    expect(native(element).id).not.toBe('');
  });

  it('defaults to a medium control', async () => {
    expect((await createInput()).size).toBe('medium');
  });

  it('carries the value as Lion s modelValue', async () => {
    const element = await createInput();

    element.modelValue = 'entryType';
    await element.updateComplete;

    expect(native(element).value).toBe('entryType');
  });

  /**
   * `maxlength` caps the value and is also the width hint: a four-character
   * field should not stretch across the page.
   */
  it('applies maxlength to the native input', async () => {
    const element = await createInput({maxlength: '12'});
    await element.updateComplete;

    expect(native(element).maxLength).toBe(12);
  });

  it('reflects the width override so the stylesheet can act on it', async () => {
    const element = await createInput({maxlength: '4', width: 'full'});

    expect(element.getAttribute('width')).toBe('full');
  });

  /** These are presentation flags the stylesheet keys off, so they reflect. */
  it('reflects its presentation flags', async () => {
    const element = await createInput({
      monospace: '',
      center: '',
      small: '',
    });

    expect(element.monospace).toBe(true);
    expect(element.center).toBe(true);
    expect(element.hasAttribute('monospace')).toBe(true);
    expect(element.hasAttribute('center')).toBe(true);
  });

  it('passes a type through to the native input', async () => {
    const element = await createInput({type: 'email'});
    await element.updateComplete;

    expect(native(element).type).toBe('email');
  });

  it('disables the native input', async () => {
    const element = await createInput({disabled: ''});
    await element.updateComplete;

    expect(native(element).disabled).toBe(true);
  });

  /**
   * `inputSize`, `min`, `max`, and `step` are properties rather than
   * attributes, and are synced onto the native input after render.
   */
  it('syncs the native-only properties onto the input', async () => {
    const element = await createInput({type: 'number'});

    element.min = 1;
    element.max = 10;
    element.step = 2;
    await element.updateComplete;

    expect(native(element).getAttribute('min')).toBe('1');
    expect(native(element).getAttribute('max')).toBe('10');
    expect(native(element).getAttribute('step')).toBe('2');
  });
});
