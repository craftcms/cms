import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import type CraftField from './field.js';
import './field.js';
import '../input/input.js';
import '../select/select.js';

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
