import {beforeEach, expect, it, vi} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import './combobox.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function fixture(configure: (control: CraftCombobox) => void = () => {}) {
  const control = document.createElement('craft-combobox');
  control.multipleChoice = true;
  control.requireOptionMatch = false;
  control.name = 'values';
  configure(control);
  const form = document.createElement('form');
  form.append(control);
  document.body.append(form);
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  return {control, form, input: control.querySelector('input')!};
}

async function type(input: HTMLInputElement, value: string) {
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await new Promise((resolve) => setTimeout(resolve));
}

function values(control: CraftCombobox) {
  return [...new FormData(control.closest('form')!).entries()];
}

it('commits free text on Enter before emitting change', async () => {
  const {control, input} = await fixture();
  const announced: unknown[] = [];
  control.addEventListener('change', () => announced.push(values(control)));
  await type(input, 'custom');
  expect(control.modelValue).toEqual([]);
  input.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'Enter', bubbles: true, composed: true})
  );
  await vi.waitFor(() => expect(control.modelValue).toEqual(['custom']));
  expect(announced).toEqual([
    [
      ['values', ''],
      ['values[]', 'custom'],
    ],
  ]);
});

it('selects option values and excludes selected options', async () => {
  const {control} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.options = [
      {label: 'Alpha', value: 'a'},
      {label: 'Beta', value: 'b'},
    ];
  });
  control.querySelectorAll<HTMLElement>('craft-option')[1]!.click();
  await vi.waitFor(() => expect(control.modelValue).toEqual(['b']));
  await control.updateComplete;
  expect(
    [...control.querySelectorAll('craft-option:not([hidden])')].map(
      (option) => (option as HTMLElement & {choiceValue: string}).choiceValue
    )
  ).toEqual(['a']);
});

it('rejects free text when an option is required', async () => {
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
  });
  await type(input, 'unknown');
  input.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'Enter', bubbles: true, composed: true})
  );
  expect(control.modelValue).toEqual([]);
});

it('omits disabled controls and prevents edits', async () => {
  const {control, input} = await fixture((c) => {
    c.disabled = true;
    c.modelValue = ['a'];
  });
  expect(values(control)).toEqual([]);
  input.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Backspace',
      bubbles: true,
      composed: true,
    })
  );
  expect(control.modelValue).toEqual(['a']);
});

it('participates in native form submission and reset without duplicate values', async () => {
  const {control, form} = await fixture((c) => {
    c.modelValue = ['a', 'b'];
  });
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'a'],
    ['values[]', 'b'],
  ]);
  control.modelValue = ['c'];
  await control.updateComplete;
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'c'],
  ]);
  form.reset();
  expect(control.modelValue).toEqual(['a', 'b']);
});

it('respects a disabled fieldset on initial connection and when toggled', async () => {
  const fieldset = document.createElement('fieldset');
  fieldset.disabled = true;
  const form = document.createElement('form');
  form.append(fieldset);
  document.body.append(form);
  const control = document.createElement('craft-combobox');
  control.multipleChoice = true;
  control.requireOptionMatch = false;
  control.name = 'values';
  control.modelValue = ['a'];
  fieldset.append(control);
  await control.updateComplete;
  expect(values(control)).toEqual([]);
  fieldset.disabled = false;
  await control.updateComplete;
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'a'],
  ]);
});

it('validates required values through the native form', async () => {
  const {control, form} = await fixture((c) => {
    c.required = true;
  });
  expect(form.checkValidity()).toBe(false);
  control.modelValue = ['a'];
  await control.updateComplete;
  expect(form.checkValidity()).toBe(true);
});

it('receives its accessible label from the Form field', async () => {
  await import('../field/field.js');
  const {computeAccessibleName} = await import('dom-accessibility-api');
  const {control, input} = await fixture();
  const field = document.createElement('craft-field');
  field.label = 'Status';
  control.slot = 'input';
  field.append(control);
  document.querySelector('form')!.append(field);
  await field.updateComplete;
  await vi.waitFor(() => expect(computeAccessibleName(input)).toBe('Status'));
  expect(computeAccessibleName(control.querySelector('[role=combobox]')!)).toBe(
    'Status'
  );
});

it('submits read-only values without allowing edits', async () => {
  const {control, input} = await fixture((c) => {
    c.readOnly = true;
    c.modelValue = ['a'];
  });
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'a'],
  ]);
  input.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Backspace',
      bubbles: true,
      composed: true,
    })
  );
  expect(control.modelValue).toEqual(['a']);
  expect(control.shadowRoot!.querySelector('button')).toBeNull();
});

it('preserves selected options beyond the limit while filtering and selecting another option', async () => {
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.limit = 2;
    c.options = Array.from({length: 20}, (_, index) => ({
      label: `Option ${index}`,
      value: String(index),
    }));
    c.modelValue = ['19'];
  });
  const changes: unknown[] = [];
  control.addEventListener('model-value-changed', () =>
    changes.push([...control.modelValue])
  );
  await type(input, 'Option 12');
  expect(control.modelValue).toEqual(['19']);
  expect(changes).toEqual([]);
  control.querySelector<HTMLElement>('craft-option:not([hidden])')!.click();
  await vi.waitFor(() => expect(control.modelValue).toEqual(['19', '12']));
  await type(input, 'no match');
  expect(control.modelValue).toEqual(['19', '12']);
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', '19'],
    ['values[]', '12'],
  ]);
});

it('keeps saved values when the options are replaced', async () => {
  const {control} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.options = [{value: 'a', label: 'Alpha'}];
    c.modelValue = ['a'];
  });
  control.options = [{value: 'b', label: 'Beta'}];
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  expect(control.modelValue).toEqual(['a']);
  expect(values(control)[1]).toEqual(['values[]', 'a']);
});

it('reads saved values from server-rendered attributes', async () => {
  document.body.innerHTML =
    '<craft-combobox multiple-choice name="values" model-value=\'["a","b"]\' options=\'[{"value":"a","label":"Alpha"}]\'></craft-combobox>';
  const control = document.querySelector('craft-combobox')!;
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  expect(control.modelValue).toEqual(['a', 'b']);
  expect(control.shadowRoot!.textContent).toContain('Alpha');
});

it('commits free text on Tab without adding duplicates', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const {control, input} = await fixture();
  const next = document.createElement('button');
  next.textContent = 'Next';
  document.body.append(next);
  await userEvent.click(input);
  await userEvent.type(input, 'custom');
  await userEvent.tab();
  expect(control.modelValue).toEqual(['custom']);
  await userEvent.click(input);
  await userEvent.type(input, 'custom');
  await userEvent.tab();
  expect(control.modelValue).toEqual(['custom']);
});

it('removes tokens and clears all values through buttons', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const {control} = await fixture((c) => {
    c.clearable = true;
    c.modelValue = ['a', 'b'];
  });
  await userEvent.click(
    control.shadowRoot!.querySelector<HTMLButtonElement>(
      '[aria-label="Remove a"]'
    )!
  );
  expect(control.modelValue).toEqual(['b']);
  await userEvent.click(
    control.shadowRoot!.querySelector<HTMLElement>('[aria-label="Clear"]')!
  );
  expect(control.modelValue).toEqual([]);
  expect(values(control)).toEqual([['values', '']]);
});

it('skips selected options when continuing keyboard selection', async () => {
  const {userEvent} = await import('@vitest/browser/context');
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.showAllOnEmpty = true;
    c.options = ['a', 'b', 'c'].map((value) => ({value, label: value}));
    c.modelValue = ['a'];
  });
  await userEvent.click(input);
  await userEvent.keyboard('{ArrowDown}');
  await userEvent.keyboard('{Enter}');
  await vi.waitFor(() => expect(control.modelValue).toHaveLength(2));
  expect(control.modelValue).toContain('a');
  expect(input.value).toBe('');
  await userEvent.keyboard('{ArrowDown}{Enter}');
  await vi.waitFor(() => expect(control.modelValue).toHaveLength(3));
});

it('announces token removal once as a user change with updated submission values', async () => {
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.options = [{value: 'a', label: 'Alpha'}];
    c.modelValue = ['a'];
  });
  const models: unknown[] = [];
  const submissions: unknown[] = [];
  control.addEventListener('model-value-changed', (event) =>
    models.push((event as CustomEvent).detail.isTriggeredByUser)
  );
  control.addEventListener('change', () => submissions.push(values(control)));
  input.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Backspace',
      bubbles: true,
      composed: true,
    })
  );
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  expect(models).toEqual([true]);
  expect(submissions).toEqual([[['values', '']]]);
});
