import {beforeEach, describe, expect, it} from 'vitest';
import type CraftInputPassword from './input-password.js';
import './input-password.js';

async function createInputPassword(): Promise<CraftInputPassword> {
  const element = document.createElement(
    'craft-input-password'
  ) as CraftInputPassword;
  element.label = 'Test Input';
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function revealButton(element: CraftInputPassword): HTMLElement | null {
  return element.querySelector<HTMLElement>('craft-button');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-password', () => {
  it('places the reveal button in the suffix slot', async () => {
    const element = await createInputPassword();
    expect(revealButton(element)?.getAttribute('slot')).toBe('suffix');
  });

  it('toggles the input type when the reveal button is clicked', async () => {
    const element = await createInputPassword();
    expect(element.type).toBe('password');

    revealButton(element)?.click();
    await element.updateComplete;
    expect(element.type).toBe('text');

    revealButton(element)?.click();
    await element.updateComplete;
    expect(element.type).toBe('password');
  });

  it('keeps the reveal button slotted across rerenders', async () => {
    const element = await createInputPassword();

    revealButton(element)?.click();
    await element.updateComplete;

    expect(element.querySelectorAll('craft-button')).toHaveLength(1);
    expect(revealButton(element)?.getAttribute('slot')).toBe('suffix');
  });

  it('forwards password rules to the native input', async () => {
    const element = await createInputPassword();
    element.passwordRules = 'minlength: 8; maxlength: 160;';
    await element.updateComplete;

    expect(element.querySelector('input')?.getAttribute('passwordrules')).toBe(
      element.passwordRules
    );
  });
});
