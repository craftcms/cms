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

  it('aggregates SSR checked state into the group value', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    await element.updateComplete;

    expect(element.value).toEqual(['image']);
  });
});

describe('semantics', () => {
  it('is a group named by its label and described by its help text', async () => {
    const element = await createFromMarkup(
      SSR_MARKUP.replace('label="Kinds"', 'label="Kinds" help-text="Pick any."')
    );
    const label = element.querySelector<HTMLElement>(
      ':scope > [slot="label"]'
    )!;
    const helpText = element.querySelector<HTMLElement>(
      ':scope > [slot="help-text"]'
    )!;

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-labelledby')).toBe(label.id);
    expect(element.getAttribute('aria-describedby')).toBe(helpText.id);
    expect(label.hasAttribute('for')).toBe(false);
  });

  it('disables every checkbox, including ones added later', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    element.disabled = true;
    await element.updateComplete;

    const added = document.createElement('craft-checkbox');
    added.value = 'audio';
    element.append(added);
    await new Promise((resolve) => setTimeout(resolve));

    expect(
      [...element.querySelectorAll('input')].map((input) => input.disabled)
    ).toEqual([true, true, true]);
  });
});

describe('value', () => {
  it('checks the options a value names, without firing events', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    const seen: string[] = [];
    element.addEventListener('change', () => seen.push('change'));

    element.value = ['video'];

    expect(
      [...element.querySelectorAll('input')].map((input) => input.checked)
    ).toEqual([false, true]);
    expect(element.value).toEqual(['video']);
    expect(seen).toEqual([]);
  });

  it('has the new value by the time a change reaches it', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    const seen: string[][] = [];
    element.addEventListener('change', () => seen.push(element.value));

    element.querySelectorAll('input')[1]!.click();

    expect(seen).toEqual([['image', 'video']]);
  });
});
