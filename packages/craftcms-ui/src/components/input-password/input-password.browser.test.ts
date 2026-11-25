import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftInputPassword from './input-password.js';
import './input-password.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(markup: string): Promise<CraftInputPassword> {
  await import('../../styles/cp.css');
  document.body.innerHTML = markup;
  const element = document.querySelector<CraftInputPassword>(
    'craft-input-password'
  )!;
  await element.updateComplete;
  return element;
}

it.each(['medium', 'small'])(
  'gives the reveal button a target of at least 24×24 CSS px at the %s size',
  async (size) => {
    const element = await render(
      `<craft-input-password label="Password" size="${size}"></craft-input-password>`
    );

    const box = element
      .querySelector(':scope > craft-button[slot="suffix"]')!
      .getBoundingClientRect();

    expect(box.width).toBeGreaterThanOrEqual(24);
    expect(box.height).toBeGreaterThanOrEqual(24);
  }
);

it('keeps the reveal button inside the field', async () => {
  const element = await render(
    '<craft-input-password label="Password"></craft-input-password>'
  );

  const field = element.querySelector('input')!.getBoundingClientRect();
  const button = element
    .querySelector(':scope > craft-button[slot="suffix"]')!
    .getBoundingClientRect();

  expect(button.top).toBeGreaterThanOrEqual(field.top);
  expect(button.bottom).toBeLessThanOrEqual(field.bottom);
});
