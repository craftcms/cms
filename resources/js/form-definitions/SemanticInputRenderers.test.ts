import {createApp, h, nextTick, ref, type Component} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import ComboboxInputRenderer from './renderers/ComboboxInputRenderer.vue';
import MoneyInputRenderer from './renderers/MoneyInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('money input renderer', () => {
  it('displays transport minor units as major units', () => {
    const cases = [
      {modelValue: 0, expected: '0.00'},
      {modelValue: 1234, expected: '12.34'},
      {modelValue: -1234, expected: '-12.34'},
      {
        modelValue: '900719925474099301',
        expected: '9007199254740993.01',
      },
      {modelValue: null, expected: ''},
      {modelValue: '', expected: ''},
      {modelValue: 1234, fractionDigits: 0, expected: '1234'},
      {modelValue: 1234, fractionDigits: 3, expected: '1.234'},
    ];

    for (const {expected, ...props} of cases) {
      const container = mount(MoneyInputRenderer, props);

      expect(container.querySelector('craft-input-money')?.value).toBe(
        expected
      );
    }
  });

  it('emits edited major units as transport minor units', () => {
    const cases = [
      {value: '0', expected: 0},
      {value: '12.34', expected: 1234},
      {value: '-12.34', expected: -1234},
      {value: '9007199254740993.01', expected: '900719925474099301'},
      {value: '', expected: null},
      {value: '1234', fractionDigits: 0, expected: 1234},
      {value: '1.234', fractionDigits: 3, expected: 1234},
    ];

    for (const {value, expected, fractionDigits} of cases) {
      const updates: unknown[] = [];
      const container = mount(MoneyInputRenderer, {
        fractionDigits,
        id: 'price',
        name: 'settings[price]',
        'onUpdate:modelValue': (value: unknown) => updates.push(value),
      });
      const input = container.querySelector('craft-input-money')!;

      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));

      expect(updates).toEqual([expected]);
      expect(input.id).toBe('price');
      expect(input.name).toBe('settings[price]');
    }
  });
});

describe('select input renderer', () => {
  it('restores each option value type after DOM selection', () => {
    const updates: unknown[] = [];
    const container = mount(SelectInputRenderer, {
      options: [
        {label: 'Numeric one', value: 1},
        {label: 'String one', value: '1'},
        {label: 'Enabled', value: true},
        {label: 'String true', value: 'true'},
        {label: 'None', value: null},
        {label: 'Empty', value: ''},
      ],
      modelValue: null,
      'onUpdate:modelValue': (value: unknown) => updates.push(value),
    });
    const select = container.querySelector('select')!;

    for (let index = 0; index < select.options.length; index++) {
      select.selectedIndex = index;
      select.dispatchEvent(new Event('change', {bubbles: true}));
    }

    expect(updates).toEqual([1, '1', true, 'true', null, '']);
  });
});

describe('checkbox select input renderer', () => {
  const options = [
    {label: 'All', value: '*'},
    {label: 'First', value: 'first'},
    {label: 'Second', value: 'second', disabled: true},
    {label: 'Third', value: 'third'},
  ];

  it('keeps authored order unless sortable values define selected order', async () => {
    const authored = mount(CheckboxSelectInputRenderer, {
      options,
      modelValue: ['third', 'first'],
      allOption: '*',
    });
    const sortable = mount(CheckboxSelectInputRenderer, {
      options,
      modelValue: ['third', 'first'],
      allOption: '*',
      sortable: true,
    });

    await nextTick();

    expect(checkboxValues(authored)).toEqual(['*', 'first', 'second', 'third']);
    expect(checkboxValues(sortable)).toEqual(['*', 'third', 'first', 'second']);
    expect(checkboxInputs(sortable).map(({disabled}) => disabled)).toEqual([
      false,
      false,
      false,
      true,
    ]);
  });

  it('preserves local sortable order and reacts to external order', async () => {
    const modelValue = ref(['third', 'first']);
    const container = document.createElement('div');
    const app = createApp({
      render: () =>
        h(CheckboxSelectInputRenderer, {
          options,
          modelValue: modelValue.value,
          allOption: '*',
          sortable: true,
          'onUpdate:modelValue': (value: unknown) => {
            modelValue.value = value as string[];
          },
        }),
    });

    document.body.appendChild(container);
    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    container.querySelectorAll('craft-reorder-button')[1]!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'up'},
      })
    );
    await nextTick();

    expect(checkboxValues(container)).toEqual([
      '*',
      'first',
      'third',
      'second',
    ]);

    const first = checkboxInputs(container)[1]!;

    first.checked = false;
    first.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();
    first.checked = true;
    first.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(modelValue.value).toEqual(['first', 'third']);
    expect(checkboxValues(container)).toEqual([
      '*',
      'first',
      'third',
      'second',
    ]);

    modelValue.value = ['third', 'first'];
    await nextTick();

    expect(checkboxValues(container)).toEqual([
      '*',
      'third',
      'first',
      'second',
    ]);
  });

  it('emits reordered selected values and preserves all-option behavior', async () => {
    const updates: unknown[] = [];
    const container = mount(CheckboxSelectInputRenderer, {
      options,
      modelValue: ['third', 'first'],
      allOption: '*',
      sortable: true,
      'onUpdate:modelValue': (value: unknown) => updates.push(value),
    });

    await nextTick();
    container.querySelectorAll('craft-reorder-button')[1]!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        detail: {direction: 'up'},
      })
    );
    await nextTick();

    expect(updates).toEqual([['first', 'third']]);
    expect(checkboxValues(container)).toEqual([
      '*',
      'first',
      'third',
      'second',
    ]);

    const allUpdates: unknown[] = [];
    const allSelectedContainer = mount(CheckboxSelectInputRenderer, {
      options,
      modelValue: '*',
      allOption: '*',
      'onUpdate:modelValue': (value: unknown) => allUpdates.push(value),
    });

    await nextTick();
    const inputs = checkboxInputs(allSelectedContainer);

    expect(inputs.map(({checked}) => checked)).toEqual([
      true,
      true,
      true,
      true,
    ]);
    expect(inputs.map(({disabled}) => disabled)).toEqual([
      false,
      true,
      true,
      true,
    ]);

    inputs[0]!.checked = false;
    inputs[0]!.dispatchEvent(new Event('change', {bubbles: true}));

    expect(allUpdates).toEqual([[]]);
  });
});

describe('combobox input renderer', () => {
  it('edits modelValue and renders alias guidance', async () => {
    const updates: unknown[] = [];
    const container = mount(ComboboxInputRenderer, {
      allowAliases: true,
      modelValue: '@storage/uploads',
      'onUpdate:modelValue': (value: unknown) => updates.push(value),
    });
    const combobox = container.querySelector('craft-combobox')!;

    await combobox.updateComplete;
    combobox.modelValue = '@web/uploads';
    combobox.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
    );

    expect(updates).toEqual(['@web/uploads']);
    expect(container.querySelector('craft-callout')?.textContent).toContain(
      'This can begin with an environment variable or alias.'
    );
  });
});

it('prevents semantic adapters from emitting while read-only', async () => {
  const updates: unknown[] = [];
  const listener = (value: unknown) => updates.push(value);
  const money = mount(MoneyInputRenderer, {
    modelValue: 100,
    readonly: true,
    'onUpdate:modelValue': listener,
  }).querySelector('craft-input-money')!;
  const select = mount(SelectInputRenderer, {
    options: [{label: 'One', value: 1}],
    readonly: true,
    'onUpdate:modelValue': listener,
  }).querySelector('select')!;
  const checkboxContainer = mount(CheckboxSelectInputRenderer, {
    options: [
      {label: 'One', value: 1},
      {label: 'Two', value: 2},
    ],
    modelValue: [1, 2],
    sortable: true,
    readonly: true,
    'onUpdate:modelValue': listener,
  });
  const checkbox = checkboxContainer.querySelector<HTMLInputElement>('input')!;
  const combobox = mount(ComboboxInputRenderer, {
    modelValue: 'one',
    readonly: true,
    'onUpdate:modelValue': listener,
  }).querySelector('craft-combobox')!;

  await combobox.updateComplete;
  money.value = '2.00';
  money.dispatchEvent(new Event('input', {bubbles: true}));
  select.value = '1';
  select.dispatchEvent(new Event('change', {bubbles: true}));
  checkbox.checked = true;
  checkbox.dispatchEvent(new Event('change', {bubbles: true}));
  checkboxContainer.querySelector('craft-reorder-button')!.dispatchEvent(
    new CustomEvent('reorder', {
      bubbles: true,
      detail: {direction: 'down'},
    })
  );
  combobox.modelValue = 'two';
  combobox.dispatchEvent(
    new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
  );

  expect(updates).toEqual([]);
});

function mount(component: Component, props: Record<string, unknown>) {
  const container = document.createElement('div');
  const app = createApp(component, props);

  document.body.appendChild(container);
  mountedApps.push(app);
  app.mount(container);

  return container;
}

function checkboxInputs(container: HTMLElement): HTMLInputElement[] {
  return Array.from(container.querySelectorAll('input[type="checkbox"]'));
}

function checkboxValues(container: HTMLElement): string[] {
  return checkboxInputs(container).map(({value}) => value);
}
