import {beforeEach, describe, expect, it} from 'vitest';
import type CraftInputCopy from './input-copy.js';
import type CraftCopyButton from '../copy-button/copy-button.js';
import './input-copy.js';

async function createInputCopy(
  attrs: Record<string, string> = {}
): Promise<CraftInputCopy> {
  const element = document.createElement('craft-input-copy') as CraftInputCopy;
  element.label = 'Test Input';
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function copyButton(element: CraftInputCopy): CraftCopyButton | null {
  return element.querySelector<CraftCopyButton>('craft-copy-button');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-copy', () => {
  it('renders a copy button in the suffix slot', async () => {
    const element = await createInputCopy();
    expect(copyButton(element)).not.toBeNull();
  });

  it('places the copy button in the suffix slot', async () => {
    const element = await createInputCopy();
    expect(copyButton(element)?.getAttribute('slot')).toBe('suffix');
  });

  it('is always read-only', async () => {
    const element = await createInputCopy();
    expect(element.readOnly).toBe(true);
  });

  it('stays read-only even if readOnly is set to false', async () => {
    const element = await createInputCopy();
    element.readOnly = false;
    await element.updateComplete;
    expect(element.readOnly).toBe(true);
  });

  it('copies the displayed value by default', async () => {
    const element = await createInputCopy({value: 'https://example.com'});
    expect(copyButton(element)?.value).toBe('https://example.com');
  });

  it('copies copy-value when provided, regardless of the displayed value', async () => {
    const element = await createInputCopy({
      value: 'sk-••••••1234',
      'copy-value': 'sk-abcdefghijklmnop1234',
    });
    expect(copyButton(element)?.value).toBe('sk-abcdefghijklmnop1234');
  });

  it('updates the copy button when modelValue changes', async () => {
    const element = await createInputCopy();
    element.modelValue = 'new value';
    await element.updateComplete;
    expect(copyButton(element)?.value).toBe('new value');
  });

  it('prefers copy-value over a changed modelValue', async () => {
    const element = await createInputCopy({'copy-value': 'fixed'});
    element.modelValue = 'something else';
    await element.updateComplete;
    expect(copyButton(element)?.value).toBe('fixed');
  });
});
