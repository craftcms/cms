import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftRadio from './radio.js';
import './radio.js';

async function createFromMarkup(markup: string): Promise<CraftRadio> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector('craft-radio') as CraftRadio;
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('SSR hydration', () => {
  it('adopts state from a slotted input instead of wiping it', async () => {
    const element = await createFromMarkup(`
      <craft-radio>
        <input slot="input" type="radio" id="r1" name="mode" value="auto" checked>
        <label slot="label" for="r1">Auto</label>
      </craft-radio>
    `);
    const input = element.querySelector('input')!;

    expect(element.name).toBe('mode');
    expect(element.checked).toBe(true);
    expect(input.name).toBe('mode');
    expect(input.checked).toBe(true);
    expect(input.value).toBe('auto');
  });

  it('keeps the label association so label clicks select', async () => {
    const element = await createFromMarkup(`
      <craft-radio>
        <input slot="input" type="radio" id="r2" name="mode" value="manual">
        <label slot="label" for="r2">Manual</label>
      </craft-radio>
    `);
    const input = element.querySelector('input')!;
    const label = element.querySelector('label')!;

    expect(label.getAttribute('for')).toBe(input.id);

    label.click();
    await element.updateComplete;

    expect(element.checked).toBe(true);
    expect(input.checked).toBe(true);
  });
});
