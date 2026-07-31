import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCheckboxGroup from './checkbox-group.js';
import './checkbox-group.js';
import '../checkbox/checkbox.js';

async function createFromMarkup(markup: string): Promise<CraftCheckboxGroup> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector(
    'craft-checkbox-group'
  ) as CraftCheckboxGroup;
  await element.updateComplete;
  return element;
}

const SSR_MARKUP = `
  <craft-checkbox-group label="Kinds">
    <div>
      <craft-checkbox>
        <input slot="input" type="checkbox" id="k1" name="kinds[]" value="image" checked>
        <label slot="label" for="k1">Image</label>
      </craft-checkbox>
    </div>
    <div>
      <craft-checkbox>
        <input slot="input" type="checkbox" id="k2" name="kinds[]" value="video">
        <label slot="label" for="k2">Video</label>
      </craft-checkbox>
    </div>
  </craft-checkbox-group>
`;

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('SSR name adoption', () => {
  it('adopts the group name from the slotted inputs instead of wiping theirs', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    const inputs = element.querySelectorAll('input[type="checkbox"]');

    expect(element.name).toBe('kinds[]');
    for (const input of inputs) {
      expect((input as HTMLInputElement).name).toBe('kinds[]');
    }
  });

  it('respects an explicit host name', async () => {
    const element = await createFromMarkup(`
      <craft-checkbox-group name="parent[]">
        <craft-checkbox>
          <input slot="input" type="checkbox" name="child[]" value="a">
        </craft-checkbox>
      </craft-checkbox-group>
    `);

    expect(element.name).toBe('parent[]');
  });

  it('aggregates SSR checked state into the group modelValue', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    await element.registrationComplete;
    await element.updateComplete;

    expect(element.modelValue).toEqual(['image']);
  });
});
