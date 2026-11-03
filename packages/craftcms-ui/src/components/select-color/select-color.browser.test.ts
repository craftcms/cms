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
    const option = [...select.querySelectorAll('craft-option')].find(
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
      const option = [...select.querySelectorAll('craft-option')].find(
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

it('takes its name and description from a surrounding field', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  document.body.innerHTML = `<craft-field label="Card color" help-text="The color of the entry type's cards."><craft-select-color slot="input" model-value="red"></craft-select-color><ul slot="feedback" class="errors"><li>Pick a color.</li></ul></craft-field>`;

  const button = page.getByRole('button', {name: /^Card color/});

  await expect.element(button).toHaveAccessibleName(/^Card color\b.*Red/);
  await expect
    .element(button)
    .toHaveAccessibleDescription(
      /The color of the entry type's cards\..*Pick a color\./
    );
});

it('focuses its button when the field label is clicked', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  document.body.innerHTML = `<craft-field label="Card color"><craft-select-color slot="input"></craft-select-color></craft-field>`;

  await page.getByText('Card color', {exact: true}).click();

  await expect
    .element(page.getByRole('button', {name: /^Card color/}))
    .toHaveFocus();
});

it('emits input then change when a color is picked, but not when one is set', async () => {
  const {page, userEvent} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const events: string[] = [];
  select.addEventListener('input', () => events.push('input'));
  select.addEventListener('change', () => events.push('change'));

  select.modelValue = 'green';
  await select.updateComplete;
  await userEvent.click(page.getByRole('button', {name: /^Color/}));
  await userEvent.click(page.getByRole('option', {name: 'Red'}));

  expect(events).toEqual(['input', 'change']);
  expect(select.modelValue).toBe('red');
});

it('selects no color until one is given', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.registrationComplete;

  expect(select.modelValue).toBe('');
  await expect
    .element(page.getByRole('option', {name: 'Red', includeHidden: true}))
    .toHaveAttribute('aria-selected', 'false');
});
