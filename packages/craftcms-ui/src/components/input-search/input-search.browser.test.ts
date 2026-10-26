import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {contrast} from '../../../test/contrast';
import type CraftInputSearch from './input-search.js';
import './input-search.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(markup: string): Promise<CraftInputSearch> {
  await import('../../styles/cp.css');
  document.body.innerHTML = markup;
  const element =
    document.querySelector<CraftInputSearch>('craft-input-search')!;
  await element.updateComplete;
  return element;
}

async function type(element: CraftInputSearch, value: string) {
  const input = element.querySelector('input')!;
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await element.updateComplete;
}

describe('craft-input-search', () => {
  it('names the input with its visually hidden label', async () => {
    const element = await render('<craft-input-search></craft-input-search>');
    await new Promise((resolve) => setTimeout(resolve));

    const input = element.querySelector('input')!;
    const label = element.querySelector<HTMLElement>(
      ':scope > [slot="label"]'
    )!;
    const labelBox = element
      .shadowRoot!.querySelector('.form-field__label')!
      .getBoundingClientRect();

    expect(input.getAttribute('aria-labelledby')?.split(' ')).toContain(
      label.id
    );
    expect(labelBox.width * labelBox.height).toBeLessThanOrEqual(1);
  });

  it('puts the clear button inside the input, ahead of other suffix content, which fills its own segment', async () => {
    const element = await render(`
      <craft-input-search style="width: 400px">
        <craft-button slot="suffix" icon size="small" variant="plain">
          <craft-icon name="filter" label="Filter"></craft-icon>
        </craft-button>
      </craft-input-search>`);
    await type(element, 'foo');
    const input = element.querySelector('input')!.getBoundingClientRect();
    const clear = element
      .querySelector(':scope > [data-search-clear]')!
      .getBoundingClientRect();
    const filter = element
      .querySelector<HTMLElement>(':scope > [slot="suffix"]')!
      .getBoundingClientRect();
    const suffix = element.shadowRoot!.querySelector('.input-group__suffix')!;
    const suffixBox = suffix.getBoundingClientRect();
    const suffixStyle = getComputedStyle(suffix);

    expect(clear.left).toBeGreaterThanOrEqual(input.left);
    expect(clear.right).toBeLessThanOrEqual(input.right);
    expect(filter.left).toBeGreaterThanOrEqual(input.right);
    expect(filter.top).toBeCloseTo(
      suffixBox.top + parseFloat(suffixStyle.borderTopWidth),
      0
    );
    expect(filter.bottom).toBeCloseTo(
      suffixBox.bottom - parseFloat(suffixStyle.borderBottomWidth),
      0
    );
    expect(filter.width).toBeCloseTo(filter.height, 0);
    expect(suffixStyle.overflow).toBe('clip');
  });

  it('draws the clear button at its own size, without a divider', async () => {
    const element = await render(
      '<craft-input-search style="width: 300px"></craft-input-search>'
    );
    await type(element, 'foo');
    const button = element.querySelector<HTMLElement>(
      ':scope > [data-search-clear]'
    )!;
    const box = button.getBoundingClientRect();
    const input = element.querySelector('input')!.getBoundingClientRect();
    const style = getComputedStyle(button);

    expect(box.height).toBeLessThan(input.height);
    expect(box.right).toBeLessThan(input.right - 1);
    expect(style.borderInlineStartColor).not.toBe(
      getComputedStyle(element.querySelector('input')!).borderTopColor
    );
    expect(style.borderTopLeftRadius).not.toBe('0px');
  });

  it('keeps the clear button square', async () => {
    const element = await render('<craft-input-search></craft-input-search>');
    await type(element, 'foo');

    const box = element
      .querySelector(':scope > [data-search-clear]')!
      .getBoundingClientRect();

    expect(box.width).toBeCloseTo(box.height, 0);
  });

  it('puts the search icon inside the input, passing clicks through to it', async () => {
    const element = await render(
      '<craft-input-search style="width: 300px"></craft-input-search>'
    );
    const input = element.querySelector('input')!;
    const icon = element
      .querySelector(':scope > craft-icon[slot="prefix"]')!
      .getBoundingClientRect();
    const prefix = getComputedStyle(
      element.shadowRoot!.querySelector('.input-group__prefix')!
    );
    const inputBox = input.getBoundingClientRect();

    expect(prefix.borderInlineEndWidth).toBe('0px');
    expect(inputBox.left).toBeLessThanOrEqual(icon.left);
    expect(
      document.elementFromPoint(
        icon.left + icon.width / 2,
        icon.top + icon.height / 2
      )
    ).toBe(input);
    expect(getComputedStyle(input).cursor).toBe('text');
    expect(icon.right).toBeLessThanOrEqual(
      inputBox.left + parseFloat(getComputedStyle(input).paddingInlineStart)
    );
  });

  it('puts the clear button inside the input, within its outer focus ring', async () => {
    const element = await render(
      '<craft-input-search style="width: 300px"></craft-input-search>'
    );
    await type(element, 'foo');
    const input = element.querySelector('input')!;
    input.focus();
    const inputBox = input.getBoundingClientRect();
    const button = element
      .querySelector(':scope > [data-search-clear]')!
      .getBoundingClientRect();
    const style = getComputedStyle(input);

    expect(button.left).toBeGreaterThanOrEqual(inputBox.left);
    expect(button.right).toBeLessThanOrEqual(inputBox.right);
    expect(parseFloat(style.paddingInlineEnd)).toBeGreaterThanOrEqual(
      button.width
    );
    expect(style.outlineStyle).toBe('solid');
    expect(parseFloat(style.outlineOffset)).toBeGreaterThanOrEqual(0);
  });

  it('keeps other suffix content in its own segment, with an inset focus ring', async () => {
    const element = await render(`
      <craft-input-search style="width: 300px">
        <craft-button slot="suffix" icon size="small" variant="plain">
          <craft-icon name="filter" label="Filter"></craft-icon>
        </craft-button>
      </craft-input-search>`);
    await type(element, 'foo');
    const input = element.querySelector('input')!;
    input.focus();
    const button = element
      .querySelector(':scope > [data-search-clear]')!
      .getBoundingClientRect();

    expect(button.right).toBeLessThanOrEqual(
      input.getBoundingClientRect().right
    );
    expect(getComputedStyle(input).outlineStyle).toBe('inset');
  });

  it('gives the clear button a target of at least 24×24 CSS px', async () => {
    const element = await render('<craft-input-search></craft-input-search>');
    await type(element, 'foo');

    const box = element
      .querySelector(':scope > craft-button')!
      .getBoundingClientRect();

    expect(box.width).toBeGreaterThanOrEqual(24);
    expect(box.height).toBeGreaterThanOrEqual(24);
  });

  it.each(['light', 'dark'])(
    'sets the search and clear icons at 3:1 against the surface in the %s theme',
    async (theme) => {
      const element = await render(`
        <div data-theme="${theme}" style="background-color: var(--c-surface-default)">
          <craft-input-search></craft-input-search>
        </div>`);
      await type(element, 'foo');
      const surface = getComputedStyle(
        document.querySelector('[data-theme]')!
      ).backgroundColor;

      for (const icon of element.querySelectorAll('craft-icon')) {
        expect(
          contrast(getComputedStyle(icon).color, surface)
        ).toBeGreaterThanOrEqual(3);
      }
    }
  );
});
