import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import type {ComboboxItem} from './combobox.js';
import './combobox.js';
import type CraftOptionGroup from '../option/option-group.js';
import type CraftOption from '../option/option.js';

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
  // Let the option render settle.
  await new Promise((resolve) => setTimeout(resolve));
  await combobox.updateComplete;
  return combobox;
}

function optionEls(combobox: CraftCombobox): CraftOption[] {
  const node = combobox.querySelector('[role=listbox]')!;
  return Array.from(node.querySelectorAll('craft-option'));
}

function activeIndex(combobox: CraftCombobox): number {
  return optionEls(combobox).findIndex((option) => option.active);
}

async function typeQuery(combobox: CraftCombobox, value: string) {
  const input = combobox.querySelector('input')!;
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

    expect(combobox.querySelector('input')!.placeholder).toBe(
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
      c.limit = 10;
      c.options = makeOptions(400);
    });
    // The whole point: DOM node count stays bounded.
    expect(optionEls(combobox)).toHaveLength(10);
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
    const combobox = await createFixture((c) => {
      c.limit = 10;
      c.options = makeOptions(301);
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

  it('groups grouped options under their labels', async () => {
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
    const group = combobox.querySelector<CraftOptionGroup>(
      '[role=listbox] craft-option-group'
    )!;
    expect(group.label).toBe('North America');
    expect(group.querySelectorAll('craft-option')).toHaveLength(2);
  });

  it('sets value to the option value (not its label) when chosen', async () => {
    const combobox = await createFixture((c) => {
      // label !== value, and custom values allowed (the General.vue "live" case)
      c.options = [
        {label: 'Online', value: '1'},
        {label: 'Offline', value: '0'},
      ];
    });

    let emitted = false;
    combobox.addEventListener('change', () => {
      emitted = true;
    });

    const offline = optionEls(combobox).find(
      (el) => el.textContent?.trim() === 'Offline'
    ) as HTMLElement;
    offline.click();
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect(combobox.value).toBe('0');
    expect(emitted).toBe(true);
  });

  it.each(['Mark', 'Markdown'])(
    'selects the filtered option when Enter is pressed for %s',
    async (query) => {
      const combobox = await createFixture((c) => {
        c.requireOptionMatch = true;
        c.options = [
          {label: 'Addresses', value: 'addresses'},
          {label: 'Markdown', value: 'markdown'},
          {label: 'Plain Text', value: 'plain-text'},
        ];
        c.value = 'plain-text';
      });
      let emitted = false;
      combobox.addEventListener('change', () => {
        emitted = true;
      });

      const input = combobox.querySelector('input')!;
      input.focus();
      await typeQuery(combobox, query);
      expect(activeIndex(combobox)).toBe(0);
      expect(String(optionEls(combobox)[0]?.value)).toBe('markdown');
      emitted = false;
      input.dispatchEvent(
        new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
      );
      await combobox.updateComplete;
      await new Promise((resolve) => setTimeout(resolve));

      expect(combobox.value).toBe('markdown');
      expect(input.value).toBe('Markdown');
      expect(emitted).toBe(true);
    }
  );

  it('lists exact matches first, so only one option looks selected', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [
        {
          type: 'optgroup',
          label: 'Routes',
          options: [
            {
              label: 'App\\Http\\Controllers\\SitePageController@exhibitions',
              value: 'App\\Http\\Controllers\\SitePageController@exhibitions',
            },
            {label: 'exhibit', value: 'exhibit'},
          ],
        },
      ];
    });

    combobox.querySelector('input')!.focus();
    await typeQuery(combobox, 'exhibit');

    const options = optionEls(combobox);
    expect(String((options[0] as HTMLElement & {value: unknown}).value)).toBe(
      'exhibit'
    );
    expect(activeIndex(combobox)).toBe(0);
    expect(
      options.filter(
        (option) =>
          option.hasAttribute('active') || option.hasAttribute('selected')
      )
    ).toHaveLength(1);
  });

  it('shows the selected option’s icon in the textbox', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'Plain Text', value: 'plain-text', data: {icon: 'i-cursor'}},
        {label: 'Money', value: 'money', data: {icon: 'euro-sign'}},
      ];
      c.value = 'money';
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
      c.value = 'plain-text';
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
      c.value = 'money';
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
    await typeQuery(combobox, '$MY_ENV_VAR');
    expect(combobox.value).toBe('$MY_ENV_VAR');

    // A matching label still maps to its value.
    await typeQuery(combobox, 'Online');
    expect(combobox.value).toBe('1');
  });

  it('clears the value via the clear button', async () => {
    const combobox = await createFixture((c) => {
      c.clearable = true;
      c.options = [{label: 'Canada', value: 'ca'}];
      c.value = 'ca';
    });
    await combobox.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    const clear = combobox.shadowRoot?.querySelector(
      '.clear'
    ) as HTMLElement | null;
    expect(clear).not.toBeNull();
    clear!.click();
    await combobox.updateComplete;

    expect(combobox.value).toBe('');
  });

  it('keeps a matching requireOptionMatch value when options are present first', async () => {
    // Regression: a value resolved before options existed was cleared to "".
    // The wrapper now sets options before value; mirror that here.
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = true;
      c.options = [
        {label: 'English (US)', value: 'en-US'},
        {label: 'French', value: 'fr'},
      ];
      c.value = 'en-US';
    });
    expect(combobox.value).toBe('en-US');
  });

  it('displays the matching option label for an initial value', async () => {
    const combobox = await createFixture();
    combobox.options = [
      {label: 'Online', value: '1'},
      {label: 'Offline', value: '0'},
    ];
    combobox.value = '1';

    await vi.waitFor(() => {
      expect(combobox.value).toBe('1');
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
    combobox.value = 'uploads';

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
      c.value = 'My Site';
    });
    expect(combobox.value).toBe('My Site');
  });

  it.each([false, true])(
    'tells a selection’s input from typing’s by its inputType (multiple: %s)',
    async (multiple) => {
      const combobox = await createFixture((c) => {
        c.multipleChoice = multiple;
        c.options = [
          {label: 'Online', value: '1'},
          {label: 'Offline', value: '0'},
        ];
      });
      const inputTypes: (string | null)[] = [];
      combobox.addEventListener('input', (event) =>
        inputTypes.push(event instanceof InputEvent ? event.inputType : null)
      );

      await typeQuery(combobox, 'Offline');
      optionEls(combobox)
        .find((el) => el.textContent?.trim() === 'Offline')!
        .click();

      expect(inputTypes).toEqual([null, 'insertReplacementText']);
    }
  );

  it('emits input for a typed (custom) value, not just selection', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    const fired: unknown[] = [];
    combobox.addEventListener('input', () => fired.push(combobox.value));

    const input = combobox.querySelector('input')!;
    input.focus();
    input.value = '$MY_ENV';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await combobox.updateComplete;

    expect(combobox.value).toBe('$MY_ENV');
    expect(fired).toEqual(['$MY_ENV']);
  });

  it('emits native input while typing and change when the field is left', async () => {
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    const order: string[] = [];
    combobox.addEventListener('input', (event) => {
      // The textbox lives in the light DOM, so its own event would otherwise
      // reach consumers with the textbox as target rather than the component.
      expect(event.target).toBe(combobox);
      order.push('input');
    });
    combobox.addEventListener('change', () => order.push('change'));

    // Cast rather than reaching through `_inputNode` directly, which is
    // protected — the older tests in this file predate that being checked.
    const input = (combobox as unknown as {_inputNode: HTMLInputElement})
      ._inputNode;
    for (const value of ['h', 'ht']) {
      input.focus();
      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));
      await combobox.updateComplete;
      await new Promise((resolve) => setTimeout(resolve));
    }

    // Typing alters but does not commit.
    expect(order).toEqual(['input', 'input']);

    input.dispatchEvent(new FocusEvent('focusout', {bubbles: true}));
    expect(order).toEqual(['input', 'input', 'change']);

    // Leaving again without an edit commits nothing.
    input.dispatchEvent(new FocusEvent('focusout', {bubbles: true}));
    expect(order).toEqual(['input', 'input', 'change']);
  });

  it('keeps updating the model past the first keystroke, even with a v-model write-back', async () => {
    // A bound v-model writes the value back on every event; that mustn't
    // wedge further edits.
    const combobox = await createFixture((c) => {
      c.requireOptionMatch = false;
      c.options = [{label: 'Online', value: '1'}];
    });
    const input = combobox.querySelector('input')!;
    // Simulate the Vue two-way binding writing the value back on each event.
    combobox.addEventListener('input', () => {
      const v = combobox.value;
      queueMicrotask(() => {
        combobox.value = v;
      });
    });

    for (const v of ['h', 'ht', 'htt', 'http']) {
      input.focus();
      input.value = v;
      input.dispatchEvent(new Event('input', {bubbles: true}));
      await combobox.updateComplete;
      await new Promise((resolve) => setTimeout(resolve));
      expect(combobox.value).toBe(v);
    }
  });

  it('opens on ArrowDown even when empty and not showAllOnEmpty', async () => {
    const combobox = await createFixture((c) => {
      c.options = [
        {label: 'United States', value: 'us'},
        {label: 'Canada', value: 'ca'},
      ];
    });
    const input = combobox.querySelector('input')!;
    input.focus();
    expect(combobox.opened).toBe(false);

    input.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
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
      c.value = '';
    });

    await vi.waitFor(() => {
      expect(combobox.querySelector('input')?.value).toBe(
        'Select a filesystem'
      );
    });
    combobox.querySelector('input')!.click();
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
      c.value = 'disk:s3';
    });

    const input = combobox.querySelector('input')!;
    input.click();
    await vi.waitFor(() => expect(combobox.opened).toBe(true));

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Local', 'S3', 'Create a new filesystem…']);

    await typeQuery(combobox, 'local');

    expect(
      optionEls(combobox).map((option) => option.textContent?.trim())
    ).toEqual(['Local']);

    optionEls(combobox)[0]!.click();
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
    await combobox.updateComplete;
    expect(
      combobox.shadowRoot!.querySelector('.combobox__footer')
    ).not.toBeNull();
  });
});

it('adopts a value naming an option from a replaced option set', async () => {
  // The field editor swaps the whole option set when the field type changes
  // and writes the new value in the same tick.
  const combobox = await createFixture((c) => {
    c.options = [
      {label: 'Plain Text', value: 'plain'},
      {label: 'Dropdown', value: 'dropdown'},
    ];
    c.value = 'plain';
  });

  const announced: Array<string | string[]> = [];
  combobox.addEventListener('input', () => announced.push(combobox.value));
  combobox.addEventListener('change', () => announced.push(combobox.value));

  combobox.options = [
    {label: 'Matrix', value: 'matrix'},
    {label: 'Money', value: 'money'},
  ];
  combobox.value = 'matrix';

  await combobox.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  await combobox.updateComplete;

  expect(combobox.value).toBe('matrix');
  // Setting the value from code is silent, as on a native control.
  expect(announced).toEqual([]);
  expect(combobox.querySelector('input')?.value).toBe('Matrix');
});
