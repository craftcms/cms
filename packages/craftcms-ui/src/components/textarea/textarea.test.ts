import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './textarea.js';
import type CraftTextarea from './textarea.js';

async function createTextarea(
  attrs: Record<string, string> = {},
  innerHTML = '<label slot="label">Description</label>'
): Promise<CraftTextarea> {
  const element = document.createElement('craft-textarea') as CraftTextarea;
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

function native(element: CraftTextarea): HTMLTextAreaElement {
  return element.querySelector('textarea')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-textarea', () => {
  /** A textarea rather than an input is the whole point of the component. */
  it('renders a native textarea, not an input', async () => {
    const element = await createTextarea();

    expect(native(element)).toBeTruthy();
    expect(element.querySelector('input')).toBeNull();
  });

  it('wires the label to the control', async () => {
    const element = await createTextarea();

    expect(element.querySelector('label')!.getAttribute('for')).toBe(
      native(element).id
    );
  });

  it('carries the value as Lion s modelValue', async () => {
    const element = await createTextarea();

    element.modelValue = 'Some description';
    await element.updateComplete;

    expect(native(element).value).toBe('Some description');
  });

  it('reflects monospace so the stylesheet can act on it', async () => {
    const element = await createTextarea({monospace: ''});

    expect(element.monospace).toBe(true);
    expect(element.hasAttribute('monospace')).toBe(true);
  });

  it('disables the native control', async () => {
    const element = await createTextarea({disabled: ''});

    expect(native(element).disabled).toBe(true);
  });
});
