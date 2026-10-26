import {afterEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './select-color.js';
import type CraftSelectColor from './select-color.js';

afterEach(() => {
  document.body.innerHTML = '';
  delete document.documentElement.dataset.theme;
});

async function swatchEdges(): Promise<Record<string, string>> {
  document.body.innerHTML = `<craft-select-color allow-transparent></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const edge = (value: string) => {
    const option = [
      ...select.shadowRoot!.querySelectorAll('craft-option'),
    ].find(
      (o) => (o as unknown as {choiceValue: string}).choiceValue === value
    )!;

    return getComputedStyle(option.querySelector('.select-color__swatch')!)
      .boxShadow;
  };

  return {blank: edge('__blank__'), black: edge('black'), blue: edge('blue')};
}

it('shadows every swatch, ringing them and lightening the no-color edge in the dark theme', async () => {
  const light = await swatchEdges();

  for (const shadow of Object.values(light)) {
    expect(shadow).toContain('rgba(0, 0, 0, 0) 0px 0px 0px 1px');
    expect(shadow).toContain('0px 1px 1px');
    expect(shadow).toContain('inset');
  }

  document.documentElement.dataset.theme = 'dark';
  const dark = await swatchEdges();

  for (const shadow of Object.values(dark)) {
    expect(shadow).toContain('rgba(0, 0, 0, 0.15) 0px 0px 0px 1px');
  }
  expect(dark.blank).not.toBe(light.blank);
});

it.each(['light', 'dark'])(
  'draws swatches like the headers of cards in their colors in the %s theme',
  async (theme) => {
    await import('../card/card.js');
    const colors = ['blue', 'black', 'white'];
    document.body.innerHTML =
      `<div data-theme="${theme}"><craft-select-color></craft-select-color>` +
      colors
        .map(
          (color) =>
            `<craft-card data-color="${color}"><span slot="label">${color}</span></craft-card>`
        )
        .join('') +
      '</div>';
    const select =
      document.querySelector<CraftSelectColor>('craft-select-color')!;
    await select.updateComplete;

    for (const color of colors) {
      const card = document.querySelector(
        `craft-card[data-color="${color}"]`
      ) as HTMLElement & {updateComplete: Promise<unknown>};
      await card.updateComplete;
      const header = getComputedStyle(
        card.shadowRoot!.querySelector('.card__header')!
      );
      const option = [
        ...select.shadowRoot!.querySelectorAll('craft-option'),
      ].find(
        (o) => (o as unknown as {choiceValue: string}).choiceValue === color
      )!;
      const swatch = getComputedStyle(
        option.querySelector('.select-color__swatch')!
      );

      // White headers are transparent over the card's white fill.
      const cardFill = getComputedStyle(
        card.shadowRoot!.querySelector('.card')!
      ).backgroundColor;
      const fill = color === 'white' ? cardFill : header.backgroundColor;
      expect(swatch.backgroundColor, color).toBe(fill);
      expect(swatch.boxShadow, color).toContain(
        `${header.borderBottomColor} 0px 0px 0px 1px inset`
      );
    }
  }
);
