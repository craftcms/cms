import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
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
