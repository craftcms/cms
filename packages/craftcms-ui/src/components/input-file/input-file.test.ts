import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './input-file.js';
import type CraftInputFile from './input-file.js';

async function createInputFile(
  attrs: Record<string, string> = {},
  innerHTML = '<label slot="label">Upload file</label>'
): Promise<CraftInputFile> {
  const element = document.createElement('craft-input-file') as CraftInputFile;
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

function native(element: CraftInputFile): HTMLInputElement {
  return element.querySelector('input[type="file"]')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-file', () => {
  it('renders a native file input', async () => {
    const element = await createInputFile();

    expect(native(element)).toBeTruthy();
  });

  /**
   * The dialog is opened by a `craft-button` the component supplies, rather
   * than by the native input's own control, so the CP's button styling
   * applies.
   */
  it('supplies its own button for the file dialog', async () => {
    const element = await createInputFile();
    const button = element.querySelector('craft-button');

    expect(button).toBeTruthy();
    expect(button!.getAttribute('type')).toBe('button');
  });

  it('wires the label to the control', async () => {
    const element = await createInputFile();

    expect(element.querySelector('label')!.getAttribute('for')).toBe(
      native(element).id
    );
  });

  it('passes accept through to the native input', async () => {
    const element = await createInputFile({accept: 'image/*'});

    expect(native(element).getAttribute('accept')).toBe('image/*');
  });

  it('passes multiple through to the native input', async () => {
    const element = await createInputFile({multiple: ''});

    expect(native(element).multiple).toBe(true);
  });

  it('disables the native input', async () => {
    const element = await createInputFile({disabled: ''});

    expect(native(element).disabled).toBe(true);
  });
});
