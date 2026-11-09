import {beforeEach, describe, expect, it} from 'vitest';
import {FieldToggle} from './field-toggle';

beforeEach(() => {
  document.body.innerHTML = '';
});

/** A stand-in for `craft-combobox`: the value lives on `modelValue`. */
function combobox(
  value: string,
  options: unknown[] = [],
  attrs: Record<string, string> = {}
): HTMLElement {
  const el = document.createElement('craft-combobox');
  Object.assign(el, {modelValue: value, options, value: 'the label'});
  for (const [name, attrValue] of Object.entries(attrs)) {
    el.setAttribute(name, attrValue);
  }
  document.body.append(el);

  return el;
}

describe('a combobox toggle', () => {
  it('is read as a select', () => {
    const toggle = combobox('a', [], {'data-target-prefix': '#pane-'});

    expect(new FieldToggle(toggle).getType()).toBe('select');
  });

  it('is read as a boolean menu when it says it is one', () => {
    const toggle = combobox('1', [], {
      'data-boolean-menu': '',
      'data-target': '#pane',
    });

    expect(new FieldToggle(toggle).getType()).toBe('booleanMenu');
  });

  it('takes its value from modelValue, not the textbox', () => {
    const toggle = combobox('chosen-value', [], {
      'data-target-prefix': '#pane-',
    });

    expect(new FieldToggle(toggle).getToggleVal()).toBe('chosen-value');
  });

  it('resolves a boolean menu from the selected option', () => {
    const options = [
      {label: 'Yes', value: '1'},
      {label: 'No', value: '0'},
      {
        type: 'optgroup',
        label: 'Environment Variables',
        options: [{label: '$OFF', value: '$OFF', data: {boolean: '0'}}],
      },
    ];

    expect(
      new FieldToggle(
        combobox('$OFF', options, {
          'data-boolean-menu': '',
          'data-target': '#pane',
        })
      ).getToggleVal()
    ).toBe(false);

    expect(
      new FieldToggle(
        combobox('1', options, {
          'data-boolean-menu': '',
          'data-target': '#pane',
        })
      ).getToggleVal()
    ).toBe(true);
  });
});

describe('a color select toggle', () => {
  it('takes its value from model-value until the select adopts it', () => {
    const toggle = document.createElement('craft-select-color');
    toggle.setAttribute('model-value', '__custom__');
    toggle.setAttribute('data-target-prefix', '#pane-');
    document.body.append(toggle);
    const fieldToggle = new FieldToggle(toggle);

    expect(fieldToggle.getType()).toBe('select');
    expect(fieldToggle.getToggleVal()).toBe('__custom__');

    Object.assign(toggle, {modelValue: '#e5422b'});

    expect(fieldToggle.getToggleVal()).toBe('-e5422b');
  });
});

describe('a switch toggle', () => {
  it('reads the checked property, which switches update before aria-checked', () => {
    const toggle = document.createElement('craft-switch-button');
    toggle.setAttribute('role', 'switch');
    toggle.setAttribute('aria-checked', 'false');
    toggle.setAttribute('data-target', '#pane');
    Object.assign(toggle, {checked: true});
    document.body.append(toggle);

    expect(new FieldToggle(toggle).getToggleVal()).toBe(true);
  });
});
