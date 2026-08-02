import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftColorPalette from './color-palette.js';
import './color-palette.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-color-palette', () => {
  it('fails loudly when its serialized value is invalid', () => {
    const malformed = document.createElement('craft-color-palette');
    const wrongShape = document.createElement('craft-color-palette');

    expect(() => malformed.setAttribute('value', '{')).toThrow(SyntaxError);
    expect(() => wrongShape.setAttribute('value', '{}')).toThrow(TypeError);
  });

  it('renders and updates nullable palette rows with accessible controls', async () => {
    const element = document.createElement('craft-color-palette');
    const listener = vi.fn();

    element.name = 'palette';
    element.modelValue = [
      {color: '#ff0000', label: 'Red', default: true},
      {color: null, label: null, default: false},
    ];
    element.addEventListener('model-value-changed', listener);
    document.body.append(element);
    await element.updateComplete;

    const root = element.shadowRoot!;
    const label = root.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-palette-label="0"]'
    )!;

    expect(root.querySelectorAll('craft-input-color')).toHaveLength(2);
    expect(root.querySelectorAll('craft-input')).toHaveLength(2);
    expect(label.getAttribute('label')).toBe('Label for Red');
    expect(label.hasAttribute('label-sr-only')).toBe(true);
    expect(
      root.querySelector('[data-palette-color="1"]')?.getAttribute('label')
    ).toBe('Color for color 2');

    label.value = 'Crimson';
    label.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    await element.updateComplete;

    expect(element.modelValue).toEqual([
      {color: '#ff0000', label: 'Crimson', default: true},
      {color: null, label: null, default: false},
    ]);
    expect(listener).toHaveBeenCalledOnce();
  });

  it('submits palette rows with the established nested names', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-color-palette');

    element.name = 'palette';
    element.modelValue = [
      {color: '#ff0000', label: 'Red', default: true},
      {color: null, label: null, default: false},
    ];
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    const data = new FormData(form);

    expect(data.get('palette[0][color]')).toBe('#ff0000');
    expect(data.get('palette[0][label]')).toBe('Red');
    expect(data.get('palette[0][default]')).toBe('1');
    expect(data.get('palette[1][color]')).toBe('');
    expect(data.get('palette[1][label]')).toBe('');
    expect(data.get('palette[1][default]')).toBe('');
  });

  it('disables every editing control when read-only', async () => {
    const element = document.createElement('craft-color-palette');

    element.modelValue = [{color: '#ff0000', label: 'Red', default: true}];
    element.readOnly = true;
    document.body.append(element);
    await element.updateComplete;

    for (const control of element.shadowRoot!.querySelectorAll<
      HTMLElement & {disabled: boolean}
    >(
      'craft-input-color, craft-input, craft-checkbox, craft-reorder-button, craft-button'
    )) {
      expect(control.disabled).toBe(true);
    }
  });
});
