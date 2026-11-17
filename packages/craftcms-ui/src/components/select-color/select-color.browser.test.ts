import {afterEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './select-color.js';
import type CraftSelectColor from './select-color.js';
import {contrast} from '../../../test/contrast';

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
      (o) => (o as unknown as {value: string}).value === value
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
        (o) => (o as unknown as {value: string}).value === color
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
  document.body.innerHTML = `<craft-field label="Card color" help-text="The color of the entry type's cards."><craft-select-color slot="input" value="red"></craft-select-color><ul slot="feedback" class="errors"><li>Pick a color.</li></ul></craft-field>`;

  const field = page.getByRole('combobox', {name: /^Card color/});

  await expect.element(field).toHaveAccessibleName('Card color');
  await expect.element(field).toHaveTextContent('Red');
  await expect
    .element(field)
    .toHaveAccessibleDescription(
      /The color of the entry type's cards\..*Pick a color\./
    );
});

it('focuses its field when the field label is clicked', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  document.body.innerHTML = `<craft-field label="Card color"><craft-select-color slot="input"></craft-select-color></craft-field>`;

  await page.getByText('Card color', {exact: true}).click();

  await expect
    .element(page.getByRole('combobox', {name: /^Card color/}))
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

  select.value = 'green';
  await select.updateComplete;
  await userEvent.click(page.getByRole('combobox', {name: /^Color/}));
  await userEvent.click(page.getByRole('option', {name: 'Red'}));

  expect(events).toEqual(['input', 'change']);
  expect(select.value).toBe('red');
});

it('keeps the highlighted color highlighted when the colors are replaced, and drops it once it’s gone', async () => {
  const {page, userEvent} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color" value="red" colors='["red","blue","green"]'></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const field = page.getByRole('combobox', {name: /^Color/});

  await userEvent.click(field);
  await userEvent.keyboard('{ArrowDown}');
  select.colors = ['red', 'blue', 'yellow'];
  await select.updateComplete;
  const blue = page.getByRole('option', {name: 'Blue'}).element();
  await expect.element(field).toHaveAttribute('aria-activedescendant', blue.id);
  await expect.element(blue).toHaveAttribute('active');

  select.colors = ['red', 'green'];
  await select.updateComplete;
  await expect.element(field).not.toHaveAttribute('aria-activedescendant');
  await userEvent.keyboard('{Enter}');

  expect(select.value).toBe('red');
});

it('selects no color until one is given', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;

  expect(select.value).toBe('');
  await expect
    .element(page.getByRole('option', {name: 'Red', includeHidden: true}))
    .toHaveAttribute('aria-selected', 'false');
});

it('is named and described by the elements its host references', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<table><thead><tr><th id="color-heading">Card color</th></tr></thead><tbody><tr><td><craft-select-color value="red" labelled-by="color-heading" described-by="color-help"></craft-select-color></td></tr></tbody></table><p id="color-help">The color of the entry type's cards.</p>`;

  const field = page.getByRole('combobox', {name: /^Card color/});

  await expect.element(field).toHaveAccessibleName('Card color');
  await expect.element(field).toHaveTextContent('Red');
  await expect
    .element(field)
    .toHaveAccessibleDescription("The color of the entry type's cards.");
});

it('names each option by its color in text', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color" allow-transparent blank-label="No color" allow-custom colors='["red","#e5422b","#2563eb"]' labels='{"#e5422b":"Craft red"}'></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;

  const names = page
    .getByRole('option', {includeHidden: true})
    .elements()
    .map((option) => option.textContent?.trim());

  expect(names).toEqual(['No color', 'Red', 'Craft red', '#2563eb', 'Custom…']);
});

it('fills hex colors’ swatches, checkers a transparent one, and selects a hex color by its value', async () => {
  document.body.innerHTML = `<craft-select-color label="Color" colors='["#e5422b","#2563eb","transparent"]' value="#2563eb"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const swatch = (value: string) =>
    [...select.querySelectorAll('craft-option')]
      .find((o) => (o as unknown as {value: string}).value === value)!
      .querySelector('.select-color__swatch')!;

  expect(getComputedStyle(swatch('#e5422b')).backgroundColor).toBe(
    'rgb(229, 66, 43)'
  );
  expect(getComputedStyle(swatch('#2563eb')).backgroundColor).toBe(
    'rgb(37, 99, 235)'
  );
  expect(getComputedStyle(swatch('transparent')).backgroundImage).toContain(
    'linear-gradient'
  );
  expect(select.value).toBe('#2563eb');
});

it('shows only the selected color’s swatch with swatch-only, naming the color as the field’s value', async () => {
  const {page, cdp} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color" swatch-only small value="red"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const field = page.getByRole('combobox', {name: /^Color/});
  const invoker = field.element() as HTMLElement;
  const swatch = invoker.querySelector<HTMLElement>('.select-color__swatch')!;
  const name = invoker.querySelector<HTMLElement>('.sr-only')!;
  const box = invoker.getBoundingClientRect();

  await expect.element(field).toHaveAccessibleName('Color');
  expect(name.textContent).toBe('Red');
  expect(name.getBoundingClientRect().width).toBeLessThanOrEqual(1);
  expect(swatch.getAttribute('aria-hidden')).toBe('true');
  expect(swatch.getBoundingClientRect().width).toBeGreaterThan(1);
  expect(invoker.shadowRoot!.querySelector('craft-icon')).toBeNull();
  expect(box.width).toBe(box.height);
  expect(box.height).toBeGreaterThanOrEqual(24);
  expect(getComputedStyle(invoker).backgroundColor).toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(invoker).borderColor).toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(invoker).boxShadow).toBe('none');

  const {frameTree} = (await cdp().send('Page.getFrameTree')) as {
    frameTree: {childFrames?: {frame: {id: string}}[]};
  };
  const values: unknown[] = [];
  for (const {frame} of frameTree.childFrames ?? []) {
    const {nodes} = (await cdp().send('Accessibility.getFullAXTree', {
      frameId: frame.id,
    })) as {nodes: {role?: {value: string}; value?: {value: unknown}}[]};
    values.push(
      ...nodes
        .filter((node) => node.role?.value === 'combobox')
        .map((node) => node.value?.value)
    );
  }
  expect(values).toEqual(['Red']);
});

it('keeps the swatch’s color in forced colors mode with swatch-only, edged in the text color', async () => {
  const {page, cdp} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<craft-select-color label="Color" swatch-only value="red"></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const swatch = () =>
    page
      .getByRole('combobox', {name: /^Color/})
      .element()
      .querySelector('.select-color__swatch')!;
  const fill = getComputedStyle(swatch()).backgroundColor;

  try {
    await cdp().send('Emulation.setEmulatedMedia', {
      features: [{name: 'forced-colors', value: 'active'}],
    });
    expect(matchMedia('(forced-colors: active)').matches).toBe(true);
    expect(getComputedStyle(swatch()).backgroundColor).toBe(fill);
    const probe = document.createElement('span');
    probe.style.color = 'CanvasText';
    document.body.append(probe);
    expect(swatchEdge(swatch())).toBe(getComputedStyle(probe).color);
  } finally {
    await cdp().send('Emulation.setEmulatedMedia', {features: []});
  }
});

function swatchEdge(swatch: Element): string {
  return getComputedStyle(swatch).boxShadow.match(
    /(rgba?\([^)]*\)) 0px 0px 0px 1px inset/
  )![1]!;
}

it.each(['light', 'dark'])(
  'edges the swatch with swatch-only so it stands out from a tinted surface in the %s theme',
  async (theme) => {
    document.body.innerHTML = `<div data-theme="${theme}" style="background:var(--c-surface-default)"><div style="padding:1rem;background:var(--c-color-neutral-fill-quiet)">${[
      '__blank__',
      'gray',
      'teal',
    ]
      .map(
        (color) =>
          `<craft-select-color label="${color}" swatch-only allow-transparent value="${color}"></craft-select-color>`
      )
      .join('')}</div></div>`;
    const surface = document.querySelector<HTMLElement>('[data-theme] > div')!;
    const behind = getComputedStyle(surface.parentElement!).backgroundColor;
    const fill = getComputedStyle(surface).backgroundColor;

    for (const color of ['__blank__', 'gray', 'teal']) {
      const select = document.querySelector<CraftSelectColor>(
        `craft-select-color[value="${color}"]`
      )!;
      await select.updateComplete;
      const swatch = select.querySelector(
        'craft-select-invoker .select-color__swatch'
      )!;

      expect(
        contrast(swatchEdge(swatch), fill, behind),
        color
      ).toBeGreaterThanOrEqual(3);
    }
  }
);
