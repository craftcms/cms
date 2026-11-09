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
  expect(control.value).toEqual([]);
  input.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'Enter', bubbles: true, composed: true})
  );
  await vi.waitFor(() => expect(control.value).toEqual(['custom']));
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
  await vi.waitFor(() => expect(control.value).toEqual(['b']));
  await control.updateComplete;
  expect(
    [...control.querySelectorAll('craft-option:not([hidden])')].map(
      (option) => (option as HTMLElement & {value: string}).value
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
  expect(control.value).toEqual([]);
});

it('omits disabled controls and prevents edits', async () => {
  const {control, input} = await fixture((c) => {
    c.disabled = true;
    c.value = ['a'];
  });
  expect(values(control)).toEqual([]);
  input.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Backspace',
      bubbles: true,
      composed: true,
    })
  );
  expect(control.value).toEqual(['a']);
});

it('participates in native form submission and reset without duplicate values', async () => {
  const {control, form} = await fixture((c) => {
    c.value = ['a', 'b'];
  });
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'a'],
    ['values[]', 'b'],
  ]);
  control.value = ['c'];
  await control.updateComplete;
  expect(values(control)).toEqual([
    ['values', ''],
    ['values[]', 'c'],
  ]);
  form.reset();
  expect(control.value).toEqual(['a', 'b']);
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
  control.value = ['a'];
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
  control.value = ['a'];
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
    c.value = ['a'];
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
  expect(control.value).toEqual(['a']);
  const token = control.querySelector('craft-combobox-token')!;
  expect(token).toHaveTextContent('a');
  expect(token.shadowRoot!.querySelector('button')).toBeNull();
});

it('preserves selected options beyond the limit while filtering and selecting another option', async () => {
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.limit = 2;
    c.options = Array.from({length: 20}, (_, index) => ({
      label: `Option ${index}`,
      value: String(index),
    }));
    c.value = ['19'];
  });
  const changes: unknown[] = [];
  control.addEventListener('change', () => changes.push([...control.value]));
  await type(input, 'Option 12');
  expect(control.value).toEqual(['19']);
  expect(changes).toEqual([]);
  control.querySelector<HTMLElement>('craft-option:not([hidden])')!.click();
  await vi.waitFor(() => expect(control.value).toEqual(['19', '12']));
  await type(input, 'no match');
  expect(control.value).toEqual(['19', '12']);
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
    c.value = ['a'];
  });
  control.options = [{value: 'b', label: 'Beta'}];
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  expect(control.value).toEqual(['a']);
  expect(values(control)[1]).toEqual(['values[]', 'a']);
});

it('reads saved values from server-rendered attributes', async () => {
  document.body.innerHTML =
    '<craft-combobox multiple-choice name="values" value=\'["a","b"]\' options=\'[{"value":"a","label":"Alpha"}]\'></craft-combobox>';
  const control = document.querySelector('craft-combobox')!;
  await control.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  expect(control.value).toEqual(['a', 'b']);
  expect(control.querySelector('ul')).toHaveTextContent('Alpha');
});

it('commits free text on Tab without adding duplicates', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {control, input} = await fixture();
  const next = document.createElement('button');
  next.textContent = 'Next';
  document.body.append(next);
  await userEvent.click(input);
  await userEvent.type(input, 'custom');
  await userEvent.tab();
  expect(control.value).toEqual(['custom']);
  await userEvent.click(input);
  await userEvent.type(input, 'custom');
  await userEvent.tab();
  expect(control.value).toEqual(['custom']);
});

it('removes tokens and clears all values through buttons', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {control} = await fixture((c) => {
    c.clearable = true;
    c.value = ['a', 'b'];
  });
  const {page} = await import('vite-plus/test/browser');
  await userEvent.click(page.getByRole('button', {name: 'a', exact: true}));
  expect(control.value).toEqual(['b']);
  await userEvent.click(
    control.shadowRoot!.querySelector<HTMLElement>('[aria-label="Clear"]')!
  );
  expect(control.value).toEqual([]);
  expect(values(control)).toEqual([['values', '']]);
});

it('skips selected options when continuing keyboard selection', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.showAllOnEmpty = true;
    c.options = ['a', 'b', 'c'].map((value) => ({value, label: value}));
    c.value = ['a'];
  });
  await userEvent.click(input);
  await userEvent.keyboard('{ArrowDown}');
  await userEvent.keyboard('{Enter}');
  await vi.waitFor(() => expect(control.value).toHaveLength(2));
  expect(control.value).toContain('a');
  expect(input.value).toBe('');
  await userEvent.keyboard('{ArrowDown}{Enter}');
  await vi.waitFor(() => expect(control.value).toHaveLength(3));
});

it('announces token removal once as a user change with updated submission values', async () => {
  const {control, input} = await fixture((c) => {
    c.requireOptionMatch = true;
    c.options = [{value: 'a', label: 'Alpha'}];
    c.value = ['a'];
  });
  const models: unknown[] = [];
  const submissions: unknown[] = [];
  control.addEventListener('input', () => models.push(control.value));
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
  expect(models).toEqual([[]]);
  expect(submissions).toEqual([[['values', '']]]);
});

it('shows each selected option’s icon or dot on its token, hidden from assistive technology', async () => {
  const {control} = await fixture((c) => {
    c.options = [
      {label: 'Starred', value: 'star', data: {icon: 'star', color: 'red'}},
      {label: 'Green', value: 'green', data: {indicator: {fill: 'green'}}},
    ];
    c.value = ['star', 'green', 'custom'];
  });
  const tokens = [...control.querySelectorAll('craft-combobox-token')];

  expect(tokens).toHaveLength(3);
  await vi.waitFor(() =>
    expect(tokens[0]!.querySelector('craft-icon')).toHaveAttribute(
      'aria-hidden',
      'true'
    )
  );
  expect(tokens[0]!.querySelector('craft-icon')).toHaveAttribute(
    'name',
    'star'
  );
  expect(tokens[1]!.querySelector('craft-indicator')).toHaveAttribute(
    'fill',
    'green'
  );
  expect(tokens[1]!.querySelector('craft-indicator')).not.toHaveAttribute(
    'label'
  );
  expect(tokens[2]!.querySelector('craft-icon, craft-indicator')).toBeNull();
});
