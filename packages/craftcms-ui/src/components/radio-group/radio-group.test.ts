import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftRadioGroup from './radio-group.js';
import './radio-group.js';
import '../radio/radio.js';

async function createFromMarkup(markup: string): Promise<CraftRadioGroup> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector(
    'craft-radio-group'
  ) as CraftRadioGroup;
  await element.updateComplete;
  return element;
}

const SSR_MARKUP = `
  <craft-radio-group label="Color">
    <div>
      <craft-radio>
        <input slot="input" type="radio" id="c1" name="color" value="red">
        <label slot="label" for="c1">Red</label>
      </craft-radio>
    </div>
    <div>
      <craft-radio>
        <input slot="input" type="radio" id="c2" name="color" value="green" checked>
        <label slot="label" for="c2">Green</label>
      </craft-radio>
    </div>
    <div>
      <craft-radio>
        <input slot="input" type="radio" id="c3" name="color" value="blue">
        <label slot="label" for="c3">Blue</label>
      </craft-radio>
    </div>
  </craft-radio-group>
`;

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('SSR name adoption', () => {
  it('adopts the group name from the slotted inputs instead of wiping theirs', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    const inputs = element.querySelectorAll('input[type="radio"]');

    expect(element.name).toBe('color');
    for (const input of inputs) {
      expect((input as HTMLInputElement).name).toBe('color');
    }
  });

  it('respects an explicit host name', async () => {
    const element = await createFromMarkup(`
      <craft-radio-group name="parent">
        <craft-radio>
          <input slot="input" type="radio" name="child" value="a">
        </craft-radio>
      </craft-radio-group>
    `);

    expect(element.name).toBe('parent');
  });

  it('aggregates SSR checked state into the group modelValue', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    await element.registrationComplete;
    await element.updateComplete;

    expect(element.modelValue).toBe('green');
  });
});
