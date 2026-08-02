import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCheckboxSelect from './checkbox-select.js';
import './checkbox-select.js';

const options = [
  {label: 'All', value: '*'},
  {label: 'First', value: 'first'},
  {label: 'Second', value: 'second', disabled: true},
  {label: 'Third', value: 'third'},
];

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-checkbox-select', () => {
  it('uses selected value order only when sortable', async () => {
    const authored = await create({modelValue: ['third', 'first']});
    const sortable = await create({
      modelValue: ['third', 'first'],
      sortable: true,
    });

    expect(values(authored)).toEqual(['*', 'first', 'second', 'third']);
    expect(values(sortable)).toEqual(['*', 'third', 'first', 'second']);
    expect(inputs(sortable).map(({disabled}) => disabled)).toEqual([
      false,
      false,
      false,
      true,
    ]);
  });

  it('emits ordered native string values', async () => {
    const element = await create({
      modelValue: ['third', 'first'],
      sortable: true,
    });
    const updates: unknown[] = [];
    element.addEventListener('model-value-changed', () => {
      updates.push(element.modelValue);
    });

    element.querySelectorAll('craft-reorder-button')[1]!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        composed: true,
        detail: {direction: 'up'},
      })
    );

    expect(values(element)).toEqual(['*', 'first', 'third', 'second']);
    expect(updates).toEqual([['first', 'third']]);

    const first = inputs(element)[1]!;
    first.checked = false;
    first.dispatchEvent(new Event('change', {bubbles: true}));
    first.checked = true;
    first.dispatchEvent(new Event('change', {bubbles: true}));

    expect(element.modelValue).toEqual(['first', 'third']);
  });

  it('preserves the all-option behavior', async () => {
    const element = await create({modelValue: '*'});
    const updates: unknown[] = [];
    element.addEventListener('model-value-changed', () => {
      updates.push(element.modelValue);
    });
    const checkboxes = inputs(element);

    expect(checkboxes.map(({checked}) => checked)).toEqual([
      true,
      true,
      true,
      true,
    ]);
    expect(checkboxes.map(({disabled}) => disabled)).toEqual([
      false,
      true,
      true,
      true,
    ]);

    checkboxes[0]!.checked = false;
    checkboxes[0]!.dispatchEvent(new Event('change', {bubbles: true}));

    expect(updates).toEqual([[]]);
  });

  it('normalizes typed option values to native strings', async () => {
    const element = await create({
      options: [
        {label: 'Two', value: 2},
        {label: 'Enabled', value: true},
        {label: 'None', value: null},
      ],
      modelValue: [2],
      allOption: undefined,
    });

    expect(values(element)).toEqual(['2', '1', '']);
    expect(inputs(element)[0]!.checked).toBe(true);

    inputs(element)[1]!.checked = true;
    inputs(element)[1]!.dispatchEvent(new Event('change', {bubbles: true}));

    expect(element.modelValue).toEqual(['2', '1']);
  });

  it('adopts server-rendered checkbox state', async () => {
    const template = document.createElement('template');
    template.innerHTML = `
      <craft-checkbox-select>
        <div class="cp-checkbox-select__item all">
          <input class="all" type="checkbox" name="sources" value="*">
        </div>
        <div class="cp-checkbox-select__item">
          <input type="checkbox" name="sources[]" value="images" checked>
        </div>
      </craft-checkbox-select>
    `;
    document.body.append(template.content);
    const element = document.body.querySelector(
      'craft-checkbox-select'
    ) as CraftCheckboxSelect;
    await element.updateComplete;

    expect(element.modelValue).toEqual(['images']);
    expect(values(element)).toEqual(['*', 'images']);
  });

  it('preserves authored SSR disabled state after unchecking all', async () => {
    const template = document.createElement('template');
    template.innerHTML = `
      <craft-checkbox-select>
        <div><input class="all" type="checkbox" value="*" checked></div>
        <div class="cp-checkbox-select__item">
          <input type="checkbox" value="images" checked disabled data-option-disabled="false">
        </div>
        <div class="cp-checkbox-select__item">
          <input type="checkbox" value="locked" checked disabled data-option-disabled="true">
        </div>
      </craft-checkbox-select>
    `;
    document.body.append(template.content);
    const element = document.body.querySelector(
      'craft-checkbox-select'
    ) as CraftCheckboxSelect;
    await element.updateComplete;
    const checkboxes = inputs(element);

    checkboxes[0]!.checked = false;
    checkboxes[0]!.dispatchEvent(new Event('change', {bubbles: true}));

    expect(checkboxes.map(({disabled}) => disabled)).toEqual([
      false,
      false,
      true,
    ]);
  });

  it('does not update while read-only', async () => {
    const element = await create({
      modelValue: ['first', 'third'],
      readOnly: true,
      sortable: true,
    });
    const updates: unknown[] = [];
    element.addEventListener('model-value-changed', () => updates.push(true));
    const checkbox = inputs(element)[1]!;

    checkbox.checked = false;
    checkbox.dispatchEvent(new Event('change', {bubbles: true}));
    element.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        bubbles: true,
        composed: true,
        detail: {direction: 'down'},
      })
    );

    expect(updates).toEqual([]);
    expect(element.modelValue).toEqual(['first', 'third']);
    expect(inputs(element).every(({disabled}) => disabled)).toBe(true);
  });
});

async function create(
  properties: Partial<CraftCheckboxSelect>
): Promise<CraftCheckboxSelect> {
  const element = document.createElement('craft-checkbox-select');
  element.id = 'sources';
  element.name = 'sources';
  element.options = options;
  element.allOption = '*';
  Object.assign(element, properties);
  document.body.append(element);
  await element.updateComplete;

  return element;
}

function inputs(element: CraftCheckboxSelect): HTMLInputElement[] {
  return Array.from(element.querySelectorAll('input[type="checkbox"]'));
}

function values(element: CraftCheckboxSelect): string[] {
  return inputs(element).map(({value}) => value);
}
