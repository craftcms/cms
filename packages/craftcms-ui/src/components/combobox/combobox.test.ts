import {beforeEach, describe, expect, it} from 'vitest';
import type CraftCombobox from './combobox.js';
import type {ComboboxItem} from './combobox.js';
import './combobox.js';

function makeOptions(count: number): ComboboxItem[] {
  return Array.from({length: count}, (_, i) => ({
    label: `Option ${i}`,
    value: `opt-${i}`,
  }));
}

async function createFixture(
  configure: (combobox: CraftCombobox) => void = () => {}
): Promise<CraftCombobox> {
  const combobox = document.createElement('craft-combobox') as CraftCombobox;
  combobox.setAttribute('label', 'Test');
  combobox.setAttribute('name', 'test');
  configure(combobox);
  document.body.append(combobox);

  await combobox.updateComplete;
  // Allow Lion's slot/registration and our option render to settle.
  await new Promise((resolve) => setTimeout(resolve));
  await combobox.updateComplete;
  return combobox;
}

function optionEls(combobox: CraftCombobox): HTMLElement[] {
  const node = combobox._listboxNode as HTMLElement;
  return Array.from(node.querySelectorAll('craft-option'));
}

async function typeQuery(combobox: CraftCombobox, value: string) {
  const input = combobox._inputNode as HTMLInputElement;
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await combobox.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-combobox', () => {
  it('renders options from the array property', async () => {
    const combobox = await createFixture((c) => {
      c.options = makeOptions(5);
    });
    expect(optionEls(combobox)).toHaveLength(5);
  });

  it('caps rendered options at the limit even with hundreds of options', async () => {
    const combobox = await createFixture((c) => {
      c.limit = 150;
      c.options = makeOptions(400);
    });
    // The whole point: DOM node count stays bounded.
    expect(optionEls(combobox).length).toBeLessThanOrEqual(150);
  });

  it('filters by label as the user types', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'United States', value: 'us'},
        {label: 'Canada', value: 'ca'},
        {label: 'Mexico', value: 'mx'},
      ];
    });
    await typeQuery(combobox, 'can');
    const labels = optionEls(combobox).map((el) => el.textContent?.trim());
    expect(labels).toEqual(['Canada']);
  });

  it('filters by keywords in option data', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'United States', value: 'us', data: {keywords: 'america usa'}},
        {label: 'Canada', value: 'ca'},
      ];
    });
    await typeQuery(combobox, 'usa');
    const labels = optionEls(combobox).map((el) => el.textContent?.trim());
    expect(labels).toEqual(['United States']);
  });

  it('renders optgroup headers for grouped options', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {
          type: 'optgroup',
          label: 'North America',
          options: [
            {label: 'Canada', value: 'ca'},
            {label: 'Mexico', value: 'mx'},
          ],
        },
      ];
    });
    const node = combobox._listboxNode as HTMLElement;
    expect(node.querySelector('.combobox__optgroup')?.textContent?.trim()).toBe(
      'North America'
    );
    expect(optionEls(combobox)).toHaveLength(2);
  });

  it('sets modelValue to the option value (not its label) when chosen', async () => {
    const combobox = await createFixture((c) => {
      // label !== value, and custom values allowed (the General.vue "live" case)
      c.options = [
        {label: 'Online', value: '1'},
        {label: 'Offline', value: '0'},
      ];
    });

    let emitted = false;
    combobox.addEventListener('model-value-changed', () => {
      emitted = true;
    });

    const offline = optionEls(combobox).find(
      (el) => el.textContent?.trim() === 'Offline'
    ) as HTMLElement;
    offline.click();
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect(combobox.modelValue).toBe('0');
    expect(emitted).toBe(true);
  });

  it('passes through custom text that matches no option label', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    expect(combobox.parser('$MY_ENV_VAR')).toBe('$MY_ENV_VAR');
    // A matching label still maps to its value.
    expect(combobox.parser('Online')).toBe('1');
  });

  it('clears the value via the clear button', async () => {
    const combobox = await createFixture((c) => {
      c.clearable = true;
      c.options = [{label: 'Canada', value: 'ca'}];
      c.modelValue = 'ca';
    });
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    const clear = combobox.shadowRoot?.querySelector(
      '.clear'
    ) as HTMLElement | null;
    expect(clear).not.toBeNull();
    clear!.click();
    await combobox.updateComplete;

    expect(combobox.modelValue).toBe('');
  });

  it('shows a footer when matches exceed the limit', async () => {
    const combobox = await createFixture((c) => {
      c.limit = 10;
      c.options = makeOptions(50);
    });
    const node = combobox._listboxNode as HTMLElement;
    expect(node.querySelector('.combobox__footer')).not.toBeNull();
  });
});
