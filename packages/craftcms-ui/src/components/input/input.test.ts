import {beforeEach, describe, expect, it} from 'vitest';
import {Required} from '@lion/ui/form-core.js';
import type CraftInput from './input.js';
import './input.js';

async function createInput(): Promise<CraftInput> {
  const element = document.createElement('craft-input') as CraftInput;
  element.label = 'Test Input';
  document.body.append(element);
  await element.updateComplete;

  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input', () => {
  it('synchronizes aria-invalid with the native input', async () => {
    const element = await createInput();
    const input = element.querySelector('input')!;

    element.setAttribute('aria-invalid', 'true');
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('true');

    element.setAttribute('aria-invalid', 'false');
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('false');
  });

  it('preserves validation-managed aria-invalid without an override', async () => {
    const element = await createInput();
    const input = element.querySelector('input')!;
    element.modelValue = '';
    element.validators = [new Required()];
    element.submitted = true;
    await element.updateComplete;
    await element.updateComplete;

    expect(input.getAttribute('aria-invalid')).toBe('true');

    element.setAttribute('aria-invalid', 'false');
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('false');

    element.removeAttribute('aria-invalid');
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('true');

    element.placeholder = 'Unrelated update';
    await element.updateComplete;
    expect(input.getAttribute('aria-invalid')).toBe('true');
  });
});
