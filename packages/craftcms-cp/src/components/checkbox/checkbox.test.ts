import {beforeEach, describe, expect, it} from 'vitest';
import type CraftCheckbox from './checkbox.js';
import './checkbox.js';

/**
 * Parses markup inertly and appends the complete subtree, matching how
 * server-rendered islands reach the page (`appendElementHtml` clones parsed
 * nodes into the container) — the element upgrades with its children present.
 */
async function createFromMarkup(markup: string): Promise<CraftCheckbox> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector(
    'craft-checkbox'
  ) as CraftCheckbox;
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('SSR hydration', () => {
  it('adopts state from a slotted input instead of wiping it', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="allowedKinds[]" value="image" checked>
        <label slot="label">Image</label>
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    expect(element.name).toBe('allowedKinds[]');
    expect(element.checked).toBe(true);
    expect(input.name).toBe('allowedKinds[]');
    expect(input.checked).toBe(true);
    expect(input.value).toBe('image');
  });

  it('adopts disabled from the slotted input', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="agree" disabled>
      </craft-checkbox>
    `);

    expect(element.disabled).toBe(true);
    expect(element.querySelector('input')!.disabled).toBe(true);
  });

  it('lets host attributes win over the slotted input', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox name="fromHost">
        <input slot="input" type="checkbox" name="fromInput">
      </craft-checkbox>
    `);

    expect(element.name).toBe('fromHost');
    expect(element.querySelector('input')!.name).toBe('fromHost');
  });

  it('keeps the label association so label clicks toggle', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" id="cb" name="agree" value="1">
        <label slot="label" for="cb">I agree</label>
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;
    const label = element.querySelector('label')!;

    expect(label.getAttribute('for')).toBe(input.id);

    label.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });

  it('still toggles through Lion after hydration', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox>
        <input slot="input" type="checkbox" name="agree" value="1">
      </craft-checkbox>
    `);
    const input = element.querySelector('input')!;

    input.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });

  it('remains client-drivable without slotted content', async () => {
    const element = await createFromMarkup(
      '<craft-checkbox name="agree" checked></craft-checkbox>'
    );
    const input = element.querySelector('input')!;

    expect(input.name).toBe('agree');
    expect(input.checked).toBe(true);
  });
});
