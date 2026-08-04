import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputColor from './input-color.js';
import './input-color.js';

async function createInputColor(): Promise<CraftInputColor> {
  const element = document.createElement('craft-input-color');
  element.label = 'Fill Color';
  document.body.append(element);
  await element.updateComplete;

  return element;
}

function textInput(element: CraftInputColor): HTMLInputElement {
  return element.querySelector('input[slot="input"]') as HTMLInputElement;
}

function pickerInput(element: CraftInputColor): HTMLInputElement {
  return element.shadowRoot?.querySelector(
    '.input-color__picker'
  ) as HTMLInputElement;
}

function preview(element: CraftInputColor): HTMLElement {
  return element.shadowRoot?.querySelector(
    '.input-color__preview'
  ) as HTMLElement;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-color', () => {
  it('strips a leading # from typed values', async () => {
    const element = await createInputColor();
    const input = textInput(element);

    input.value = '#abc';
    input.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );
    await element.updateComplete;

    expect(element.modelValue).toBe('abc');
    expect(input.value).toBe('abc');
  });

  it('preserves shorthand values while expanding the picker and preview values', async () => {
    const element = await createInputColor();

    element.modelValue = 'abc';
    await element.updateComplete;

    expect(element.modelValue).toBe('abc');
    expect(pickerInput(element).value).toBe('#aabbcc');
    expect(preview(element).getAttribute('style')).toContain(
      'background-color: #aabbcc'
    );
  });

  it('keeps invalid text values and clears the preview', async () => {
    const element = await createInputColor();

    element.modelValue = 'not-a-color';
    await element.updateComplete;

    expect(textInput(element).value).toBe('not-a-color');
    expect(preview(element).getAttribute('style')).toBe('');
  });

  it('updates the model from the native color picker without a # prefix', async () => {
    const element = await createInputColor();
    const picker = pickerInput(element);

    picker.value = '#112233';
    picker.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );
    await element.updateComplete;

    expect(element.modelValue).toBe('112233');
    expect(textInput(element).value).toBe('112233');
  });

  it('parses presets from a JSON attribute and normalizes them for the picker datalist', async () => {
    const element = await createInputColor();

    element.setAttribute('presets', '["abc", "#112233", "not-a-color"]');
    await element.updateComplete;

    const options = [
      ...element.shadowRoot!.querySelectorAll('datalist option'),
    ].map((option) => option.getAttribute('value'));

    expect(options).toEqual(['#aabbcc', '#112233']);
  });

  it('sets disabled state on the native picker', async () => {
    const element = await createInputColor();

    element.disabled = true;
    await element.updateComplete;

    expect(pickerInput(element).disabled).toBe(true);
  });

  it('submits the no-# value with native forms', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-input-color');

    element.name = 'fill';
    form.append(element);
    document.body.append(form);
    element.modelValue = '#abc';
    await element.updateComplete;

    expect(new FormData(form).get('fill')).toBe('abc');

    element.disabled = true;
    await element.updateComplete;

    expect(new FormData(form).has('fill')).toBe(false);
  });
});
