import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputColor from './input-color.js';
import './input-color.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(): Promise<CraftInputColor> {
  await import('../../styles/cp.css');
  document.body.innerHTML =
    '<craft-input-color label="Color" value="4f7cff"></craft-input-color>';
  const element = document.querySelector<CraftInputColor>('craft-input-color')!;
  await element.updateComplete;
  return element;
}

function hashBox(element: CraftInputColor): DOMRect {
  const range = document.createRange();
  range.selectNodeContents(
    element.shadowRoot!.querySelector('.input-group__prefix')!
  );
  return range.getBoundingClientRect();
}

describe('craft-input-color', () => {
  it('sends a click on the # to the text field, with a text cursor', async () => {
    const element = await render();
    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!;
    const hash = hashBox(element);

    const hit = document.elementFromPoint(
      hash.left + hash.width / 2,
      hash.top + hash.height / 2
    );

    expect(hit).toBe(input);
    expect(getComputedStyle(hit!).cursor).toBe('text');
  });

  it('starts the value just after the #, in the same font', async () => {
    const element = await render();
    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!;
    const style = getComputedStyle(input);
    const valueStart =
      input.getBoundingClientRect().left +
      parseFloat(style.borderLeftWidth) +
      parseFloat(style.paddingLeft);
    const prefixStyle = getComputedStyle(
      element.shadowRoot!.querySelector('.input-group__prefix')!
    );

    expect(style.fontFamily).toBe(prefixStyle.fontFamily);
    const gap = valueStart - hashBox(element).right;
    expect(gap).toBeGreaterThan(0);
    expect(gap).toBeLessThanOrEqual(4);
  });

  it('draws the focus ring outside the text field’s border', async () => {
    const element = await render();
    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!;

    input.focus();
    const style = getComputedStyle(input);

    expect(style.outlineStyle).toBe('solid');
    expect(parseFloat(style.outlineOffset)).toBeGreaterThan(0);
  });
});
