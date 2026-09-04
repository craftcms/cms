import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
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
  it('forwards its placeholder to the textbox', async () => {
    const combobox = await createFixture((c) => {
      c.placeholder = 'Choose an option';
    });

    expect((combobox._inputNode as HTMLInputElement).placeholder).toBe(
      'Choose an option'
    );
  });

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

  it('re-renders cleanly across successive filters without duplicating text', async () => {
    // Regression: Lion's match-highlighting used to mutate option DOM and
    // collide with our lit-html render, producing e.g. "Option 000Option 300".
    const combobox = await createFixture((c) => {
      c.options = makeOptions(400);
    });
    for (const q of ['O', 'Op', 'Option 3', 'Option 30', 'Option 300']) {
      await typeQuery(combobox, q);
    }
    const labels = optionEls(combobox).map((el) => el.textContent?.trim());
    expect(labels).toEqual(['Option 300']);
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

  it('shows the selected option’s icon in the textbox', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'Plain Text', value: 'plain-text', data: {icon: 'i-cursor'}},
        {label: 'Money', value: 'money', data: {icon: 'euro-sign'}},
      ];
      c.modelValue = 'money';
    });

    const icon = combobox.shadowRoot?.querySelector('craft-icon.prefix');
    expect(icon?.getAttribute('name')).toBe('euro-sign');
    expect(combobox.hasAttribute('has-prefix-icon')).toBe(true);
  });

  it('swaps the textbox icon when another option is chosen', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'Plain Text', value: 'plain-text', data: {icon: 'i-cursor'}},
        {label: 'Money', value: 'money', data: {icon: 'euro-sign'}},
      ];
      c.modelValue = 'plain-text';
    });

    const money = optionEls(combobox).find(
      (el) => el.textContent?.trim() === 'Money'
    ) as HTMLElement;
    money.click();
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect(
      combobox.shadowRoot
        ?.querySelector('craft-icon.prefix')
        ?.getAttribute('name')
    ).toBe('euro-sign');
  });

  it('drops the textbox icon while the value is free text', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Money', value: 'money', data: {icon: 'euro-sign'}}];
      c.modelValue = 'money';
    });

    await typeQuery(combobox, 'Mon');

    expect(combobox.shadowRoot?.querySelector('craft-icon.prefix')).toBeNull();
    expect(combobox.hasAttribute('has-prefix-icon')).toBe(false);
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

  it('keeps a matching requireOptionMatch value when options are present first', async () => {
    // Regression: a value resolved before options existed was cleared to "".
    // The wrapper now sets options before modelValue; mirror that here.
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = true;
      c.options = [
        {label: 'English (US)', value: 'en-US'},
        {label: 'French', value: 'fr'},
      ];
      c.modelValue = 'en-US';
    });
    expect(combobox.modelValue).toBe('en-US');
  });

  it('displays the matching option label for an initial value', async () => {
    const combobox = await createFixture();
    combobox.options = [
      {label: 'Online', value: '1'},
      {label: 'Offline', value: '0'},
    ];
    combobox.modelValue = '1';

    await vi.waitFor(() => {
      expect(combobox.modelValue).toBe('1');
      expect(combobox.querySelector('input')?.value).toBe('Online');
    });
  });

  it('includes the matching option hint when selected hints are enabled', async () => {
    const combobox = await createFixture();
    combobox.options = [
      {
        label: 'Uploads',
        value: 'uploads',
        data: {hint: 's3://uploads'},
      },
    ];
    combobox.showSelectedHint = true;
    combobox.modelValue = 'uploads';

    await vi.waitFor(() => {
      expect(combobox.querySelector('input')?.value).toBe(
        'Uploads – s3://uploads'
      );
    });
  });

  it('preserves a custom value on mount', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: '$SITE_NAME', value: '$SITE_NAME'}];
      c.modelValue = 'My Site';
    });
    expect(combobox.modelValue).toBe('My Site');
  });

  it('emits model-value-changed for a typed (custom) value, not just selection', async () => {
    // Lion only emits by repropagating a checked option's event; free text
    // (requireOptionMatch=false) has no option to repropagate, so without our
    // fix the model updates silently and v-model bindings never see it.
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    const fired: Array<{value: unknown; changeSource?: string}> = [];
    combobox.addEventListener('model-value-changed', (event) => {
      fired.push({
        value: combobox.modelValue,
        changeSource: (event as CustomEvent).detail?.changeSource,
      });
    });

    const input = combobox._inputNode as HTMLInputElement;
    input.focus();
    input.value = '$MY_ENV';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect(combobox.modelValue).toBe('$MY_ENV');
    expect(fired).toContainEqual({
      value: '$MY_ENV',
      changeSource: 'input',
    });
  });

  it('keeps updating the model past the first keystroke, even with a v-model write-back', async () => {
    // Regression: list-mode autocomplete stopped syncing typed text into the
    // model after the first character, and a bound v-model writing .modelValue
    // back used to wedge further edits.
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    const input = combobox._inputNode as HTMLInputElement;
    // Simulate the Vue two-way binding writing the value back on each event.
    combobox.addEventListener('model-value-changed', () => {
      const v = combobox.modelValue;
      queueMicrotask(() => {
        combobox.modelValue = v;
      });
    });

    for (const v of ['h', 'ht', 'htt', 'http']) {
      input.focus();
      input.value = v;
      input.dispatchEvent(new Event('input', {bubbles: true}));
      await combobox.updateComplete;
      await new Promise((resolve) => setTimeout(resolve));
      expect(combobox.modelValue).toBe(v);
    }
  });

  it('opens on ArrowDown even when empty and not showAllOnEmpty', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'United States', value: 'us'},
        {label: 'Canada', value: 'ca'},
      ];
    });
    const input = combobox._inputNode as HTMLInputElement;
    input.focus();
    expect(combobox.opened).toBe(false);

    input.dispatchEvent(
      new KeyboardEvent('keyup', {key: 'ArrowDown', bubbles: true})
    );
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect(combobox.opened).toBe(true);
    expect(optionEls(combobox).length).toBe(2);
  });

  it('does not filter by an empty option label', async () => {
    const combobox = await createFixture((c) => {
      c.showAllOnEmpty = true;
      c.options = [
        {label: 'Select a filesystem', value: ''},
        {label: 'Create a new filesystem…', value: '__add__'},
      ];
      c.modelValue = '';
    });

    await vi.waitFor(() => {
      expect(combobox.querySelector('input')?.value).toBe(
        'Select a filesystem'
      );
    });
    (combobox._inputNode as HTMLInputElement).click();
    await vi.waitFor(() => expect(combobox.opened).toBe(true));

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Select a filesystem', 'Create a new filesystem…']);
  });

  it('shows all options when reopening with a selected value', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'Local', value: 'local'},
        {label: 'S3', value: 'disk:s3', data: {hint: 'disk:s3'}},
        {label: 'Create a new filesystem…', value: '__add__'},
      ];
      c.showSelectedHint = true;
      c.modelValue = 'disk:s3';
    });

    const input = combobox._inputNode as HTMLInputElement;
    input.click();
    await vi.waitFor(() => expect(combobox.opened).toBe(true));

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Local', 'S3', 'Create a new filesystem…']);

    await typeQuery(combobox, 'local');

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Local']);

    optionEls(combobox)[0].click();
    await vi.waitFor(() => expect(combobox.opened).toBe(false));
    await combobox.updateComplete;

    input.click();
    await vi.waitFor(() => expect(combobox.opened).toBe(true));

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Local', 'S3', 'Create a new filesystem…']);
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
