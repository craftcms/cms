import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import {computeAccessibleName} from 'dom-accessibility-api';
import type CraftField from './field.js';
import './field.js';
import '../input/input.js';
import '../select/select.js';
import '../combobox/combobox.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('labels nested native controls', async () => {
  document.body.innerHTML =
    '<craft-field label="Operator"><craft-select slot="input"><select id="form-operator" slot="input"><option>equals</option></select></craft-select></craft-field><craft-field label="Title"><craft-input slot="input"><input id="form-value" slot="input" name="value"></craft-input></craft-field>';
  await vi.waitFor(() =>
    expect(computeAccessibleName(document.querySelector('input')!)).toBe(
      'Title'
    )
  );
  expect(computeAccessibleName(document.querySelector('select')!)).toBe(
    'Operator'
  );
});

it.each([false, true])(
  'labels a nested combobox and its listbox once (multiple: %s)',
  async (multiple) => {
    document.body.innerHTML = `<craft-field label="Entry Type" label-sr-only><craft-combobox slot="input" ${multiple ? 'multiple-choice' : ''} options='[{"label":"Alpha","value":"a"}]'></craft-combobox></craft-field>`;
    const field = document.querySelector('craft-field')!;
    const combobox = document.querySelector('craft-combobox')!;
    await field.updateComplete;
    await combobox.updateComplete;
    // Picking up the field's label queues another update, which names the listbox.
    await combobox.updateComplete;
    expect(
      computeAccessibleName(combobox.querySelector('input:not([type=hidden])')!)
    ).toBe('Entry Type');
    expect(
      computeAccessibleName(combobox.querySelector('[role=listbox]')!)
    ).toBe('Entry Type');
  }
);

describe('spacing', () => {
  async function renderField(attrs: string): Promise<CraftField> {
    document.body.innerHTML = `<craft-field ${attrs}><input slot="input"></craft-field>`;
    const field = document.querySelector('craft-field')!;
    await field.updateComplete;
    return field;
  }

  /** Distance from the top of the field to the top of the input group. */
  function inputOffset(field: CraftField): number {
    const inputGroup = field.shadowRoot!.querySelector(
      '.form-field__group-two'
    )!;
    return (
      inputGroup.getBoundingClientRect().top - field.getBoundingClientRect().top
    );
  }

  it('leaves no space above the input without a label', async () => {
    expect(inputOffset(await renderField(''))).toBe(0);
  });

  it('leaves no space above the input for a visually hidden label', async () => {
    const field = await renderField('label="Title" label-sr-only');

    expect(inputOffset(field)).toBe(0);
    expect(computeAccessibleName(field.querySelector('input')!)).toBe('Title');
  });

  it('spaces instructions from the input when the label is visually hidden', async () => {
    const field = await renderField(
      'label="Title" label-sr-only help-text="Some instructions"'
    );
    const helpText = field.shadowRoot!.querySelector('.form-field__help-text')!;

    expect(
      helpText.getBoundingClientRect().top - field.getBoundingClientRect().top
    ).toBe(0);
    expect(inputOffset(field)).toBeGreaterThan(
      helpText.getBoundingClientRect().height
    );
  });
});

it('exposes translation help on keyboard focus and dismisses it with Escape', async () => {
  const field = document.createElement('craft-field');
  field.label = 'Title';
  field.translatable = true;
  field.translationDescription = 'Translated per site.';
  field.innerHTML = '<input slot="input" type="text">';
  document.body.append(field);
  await field.updateComplete;

  const tooltip = field.querySelector('craft-tooltip')!;
  const indicator = tooltip.querySelector('craft-button')!;

  await expect.element(indicator).toBeVisible();
  expect(indicator.getBoundingClientRect().width).toBeGreaterThanOrEqual(24);
  expect(indicator.getBoundingClientRect().height).toBeGreaterThanOrEqual(24);
  await expect.element(indicator).toHaveAccessibleName('Translated per site.');
  await userEvent.tab();
  expect(document.activeElement).toBe(indicator);
  await expect.poll(() => tooltip.opened).toBe(true);
  await expect
    .element(tooltip.querySelector<HTMLElement>('[slot="content"]')!)
    .toHaveTextContent('Translated per site.');

  await userEvent.keyboard('{Escape}');
  await expect.poll(() => tooltip.opened).toBe(false);
  expect(document.activeElement).toBe(indicator);

  await userEvent.tab();
  expect(document.activeElement).toBe(field.querySelector('input'));
});
