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

  it('aggregates SSR checked state into the group value', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    await element.updateComplete;

    expect(element.value).toBe('green');
  });
});

describe('button-group appearance', () => {
  it('keeps each option a named radio input', async () => {
    const element = await createFromMarkup(
      SSR_MARKUP.replace(
        '<craft-radio-group',
        '<craft-radio-group appearance="button-group"'
      )
    );
    await element.updateComplete;

    const radios = Array.from(element.querySelectorAll('craft-radio'));
    const inputs = radios.map((radio) => radio.querySelector('input')!);

    expect(element.getAttribute('role')).toBe('radiogroup');
    expect(inputs.map((input) => input.type)).toEqual([
      'radio',
      'radio',
      'radio',
    ]);
    expect(inputs.map((input) => input.labels?.[0]?.textContent)).toEqual([
      'Red',
      'Green',
      'Blue',
    ]);
    expect(radios.map((radio) => radio.hasAttribute('data-button'))).toEqual([
      true,
      true,
      true,
    ]);
    expect(
      radios.map((radio) => [
        radio.hasAttribute('data-button-start'),
        radio.hasAttribute('data-button-end'),
      ])
    ).toEqual([
      [true, false],
      [false, false],
      [false, true],
    ]);
  });

  it('stops drawing buttons when turned off', async () => {
    const element = await createFromMarkup(
      SSR_MARKUP.replace(
        '<craft-radio-group',
        '<craft-radio-group appearance="button-group"'
      )
    );

    element.appearance = undefined;
    await element.updateComplete;

    const radios = Array.from(element.querySelectorAll('craft-radio'));

    expect(radios.some((radio) => radio.hasAttribute('data-button'))).toBe(
      false
    );
    expect(
      radios.some((radio) => radio.hasAttribute('data-button-start'))
    ).toBe(false);
  });
});

describe('semantics', () => {
  it('is a radiogroup named by its label, marked required and invalid', async () => {
    const element = await createFromMarkup(
      SSR_MARKUP.replace('label="Color"', 'label="Color" required invalid')
    );
    const label = element.querySelector<HTMLElement>(
      ':scope > [slot="label"]'
    )!;

    expect(element.getAttribute('role')).toBe('radiogroup');
    expect(element.getAttribute('aria-labelledby')).toBe(label.id);
    expect(element.getAttribute('aria-required')).toBe('true');
    expect(element.getAttribute('aria-invalid')).toBe('true');
  });

  it('disables every option', async () => {
    const element = await createFromMarkup(SSR_MARKUP);
    element.disabled = true;
    await element.updateComplete;

    expect(
      [...element.querySelectorAll('input')].every((input) => input.disabled)
    ).toBe(true);
  });
});

describe('value', () => {
  const NAMELESS = `
    <craft-radio-group label="Size">
      <craft-radio value="s" label="Small" checked></craft-radio>
      <craft-radio value="m" label="Medium"></craft-radio>
      <craft-radio value="l" label="Large"></craft-radio>
    </craft-radio-group>
  `;

  it('keeps one option selected even when the radios share no name', async () => {
    const element = await createFromMarkup(NAMELESS);
    const radios = [...element.querySelectorAll('craft-radio')];
    const seen: string[] = [];
    element.addEventListener('change', () => seen.push(element.value));

    radios[2]!.querySelector('input')!.click();
    await element.updateComplete;

    expect(seen).toEqual(['l']);
    expect(radios.map((radio) => radio.checked)).toEqual([false, false, true]);
    expect(radios.map((radio) => radio.hasAttribute('checked'))).toEqual([
      false,
      false,
      true,
    ]);
  });

  it('selects the option a value names, or none', async () => {
    const element = await createFromMarkup(NAMELESS);

    element.value = 'm';
    expect(element.value).toBe('m');

    element.value = '';
    expect(element.value).toBe('');
    expect(
      [...element.querySelectorAll('craft-radio')].some(
        (radio) => radio.checked
      )
    ).toBe(false);
  });

  it('applies a value set before its options arrive', async () => {
    const element = await createFromMarkup(
      '<craft-radio-group label="Size"></craft-radio-group>'
    );
    element.value = 'm';

    element.innerHTML = `
      <craft-radio value="s" label="Small"></craft-radio>
      <craft-radio value="m" label="Medium"></craft-radio>
    `;
    await new Promise((resolve) => setTimeout(resolve));

    expect(element.value).toBe('m');
  });
});
