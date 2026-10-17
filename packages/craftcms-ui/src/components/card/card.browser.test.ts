import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './card.js';
import '../chip/chip.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../badge/badge.js';
import {contrast} from '../../../test/contrast';
import type CraftCard from './card.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('gives a white card a body as white as its header', async () => {
  document.body.innerHTML = `
    <div style="background: var(--c-surface-sunken)">
      <craft-card data-color="white">
        <span slot="label">Header</span>
        Body
      </craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const root = card.shadowRoot!.querySelector<HTMLElement>('.card')!;
  const header = card.shadowRoot!.querySelector<HTMLElement>('.card__header')!;
  const opaque = (color: string) =>
    !/rgba\(.*, 0\)|\/ 0(\.\d+)?\)|transparent/.test(color);

  // Whichever element carries the fill, the body and header read the same.
  const body = getComputedStyle(root).backgroundColor;
  const headerFill = getComputedStyle(header).backgroundColor;
  expect(opaque(body)).toBe(true);
  expect(opaque(headerFill) ? headerFill : body).toBe(body);
});

it('gives a colored card’s links its text color and an underline', async () => {
  document.body.innerHTML = ['red', 'white', 'gray', 'slate', 'black']
    .map(
      (color) =>
        `<craft-card data-color="${color}">Body with <a href="#">a link</a></craft-card>`
    )
    .join('');
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));

  const link = (card: CraftCard) => getComputedStyle(card.querySelector('a')!);
  const text = (card: CraftCard) =>
    getComputedStyle(card.shadowRoot!.querySelector('.card')!).color;
  const [red, ...neutral] = cards;

  expect(link(red!).color).toBe(text(red!));
  expect(link(red!).textDecorationLine).toBe('underline');

  // The neutral colors and black keep the default link styling.
  for (const card of neutral) {
    expect(link(card).color).not.toBe(text(card));
    expect(link(card).textDecorationLine).toBe('none');
  }
});

it('gives a black card’s links a blue that holds up against its fill, in either theme', async () => {
  for (const theme of ['light', 'dark']) {
    document.body.innerHTML = `
      <div data-theme="${theme}">
        <craft-card data-color="black">Body with <a href="#">a link</a></craft-card>
      </div>`;
    const card = document.querySelector<CraftCard>('craft-card')!;
    await card.updateComplete;

    expect(
      contrast(
        getComputedStyle(card.querySelector('a')!).color,
        getComputedStyle(card.shadowRoot!.querySelector('.card')!)
          .backgroundColor
      ),
      theme
    ).toBeGreaterThanOrEqual(4.5);
  }
});

it('gives a white card a lighter shadow than other cards', async () => {
  document.body.innerHTML = `
    <span class="xs" style="box-shadow: var(--c-shadow-xs)"></span>
    <span class="sm" style="box-shadow: var(--c-shadow-sm)"></span>
    <craft-card data-color="white">White</craft-card>
    <craft-card>Plain</craft-card>`;
  const [white, plain] = Array.from(
    document.querySelectorAll<CraftCard>('craft-card')
  );
  await Promise.all([white!.updateComplete, plain!.updateComplete]);

  const shadow = (card: CraftCard) =>
    getComputedStyle(card.shadowRoot!.querySelector('.card')!).boxShadow;

  expect(shadow(white!)).toBe(
    getComputedStyle(document.querySelector('.xs')!).boxShadow
  );
  expect(shadow(plain!)).toBe(
    getComputedStyle(document.querySelector('.sm')!).boxShadow
  );
});

it('gives a white card the same border color and shadow as a white chip', async () => {
  document.body.innerHTML = `
    <craft-card data-color="white">Card</craft-card>
    <craft-chip data-color="white">Chip</craft-chip>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  const chip = document.querySelector('craft-chip')!;
  await Promise.all([card.updateComplete, chip.updateComplete]);

  const cardStyle = getComputedStyle(card.shadowRoot!.querySelector('.card')!);
  const chipStyle = getComputedStyle(
    chip.shadowRoot!.querySelector('.cp-chip')!
  );

  expect(cardStyle.borderTopColor).toBe(chipStyle.borderTopColor);
  expect(cardStyle.boxShadow).toBe(chipStyle.boxShadow);
});

it('fits its border inside a stretched grid cell', async () => {
  document.body.innerHTML = `
    <div style="display: grid; grid-template-rows: 120px">
      <craft-card>Body</craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const host = card.getBoundingClientRect();
  const inner = card
    .shadowRoot!.querySelector('.card')!
    .getBoundingClientRect();

  expect(inner.height).toBe(host.height);
  expect(inner.bottom).toBe(host.bottom);
});

it('divides a white card’s header and footer with the table border color', async () => {
  document.body.innerHTML = `
    <span class="probe" style="border: 1px solid var(--c-table-border-color)"></span>
    <craft-card data-color="white">
      <span slot="label">Header</span>
      Body
      <span slot="footer">Footer</span>
    </craft-card>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const expected = getComputedStyle(
    document.querySelector('.probe')!
  ).borderTopColor;
  const part = (selector: string) =>
    getComputedStyle(card.shadowRoot!.querySelector(selector)!);

  expect(part('.card__header').borderBottomColor).toBe(expected);
  expect(part('.card__footer').borderTopColor).toBe(expected);
  expect(part('.card').borderTopColor).not.toBe(expected);
});

it('styles a selected white card like a selected chip', async () => {
  document.body.innerHTML = `
    <craft-card data-color="white" active>
      <span slot="label">Header</span>
      Body
      <span slot="footer">Footer</span>
    </craft-card>
    <craft-chip data-color="white" selected>Chip</craft-chip>
    <span class="divider" style="border: 1px solid var(--c-color-accent-border-quiet)"></span>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  const chip = document.querySelector('craft-chip')!;
  await Promise.all([card.updateComplete, chip.updateComplete]);

  const part = (selector: string) =>
    getComputedStyle(card.shadowRoot!.querySelector(selector)!);
  const chipStyle = getComputedStyle(
    chip.shadowRoot!.querySelector('.cp-chip')!
  );
  const divider = getComputedStyle(
    document.querySelector('.divider')!
  ).borderTopColor;

  expect(part('.card').backgroundColor).toBe(chipStyle.backgroundColor);
  expect(part('.card').borderTopColor).toBe(chipStyle.borderTopColor);
  expect(part('.card__header').backgroundColor).toBe('rgba(0, 0, 0, 0)');
  expect(part('.card__header').borderBottomColor).toBe(divider);
  expect(part('.card__footer').borderTopColor).toBe(divider);
});

it('gives a white card the raised surface and text of the dark theme', async () => {
  document.body.innerHTML = `
    <div data-theme="dark">
      <span class="probe" style="background-color: var(--c-surface-raised); color: var(--c-text-default)"></span>
      <craft-card data-color="white">
        <span slot="label">Header</span>
        Body
      </craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const probe = getComputedStyle(document.querySelector('.probe')!);
  const root = getComputedStyle(card.shadowRoot!.querySelector('.card')!);
  const header = getComputedStyle(
    card.shadowRoot!.querySelector('.card__header')!
  );

  expect(root.backgroundColor).toBe(probe.backgroundColor);
  expect(root.color).toBe(probe.color);
  expect(header.color).toBe(probe.color);
});

function channels(color: string): {rgb: number[]; alpha: number} {
  const values = color.match(/[\d.]+/g)!.map(Number);
  const srgb = color.startsWith('color(');
  const rgb = values.slice(0, 3).map((value) => (srgb ? value * 255 : value));

  return {rgb, alpha: values[3] ?? 1};
}

/** The luminance of a possibly translucent color drawn over an opaque one. */
function luminanceOver(color: string, background: string): number {
  const {rgb, alpha} = channels(color);
  const base = channels(background).rgb;
  const [r, g, b] = rgb.map((value, i) => {
    const channel = (value * alpha + base[i]! * (1 - alpha)) / 255;
    return channel <= 0.03928
      ? channel / 12.92
      : ((channel + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
}

it.each([['data-color="white"'], ['']])(
  'draws a card’s border lighter than its fill in the dark theme (%s)',
  async (attrs) => {
    document.body.innerHTML = `
      <div data-theme="dark" style="background-color: var(--c-surface-raised)">
        <craft-card ${attrs}>Body</craft-card>
      </div>`;
    const card = document.querySelector<CraftCard>('craft-card')!;
    await card.updateComplete;

    const surface = getComputedStyle(card.parentElement!).backgroundColor;
    const border = getComputedStyle(
      card.shadowRoot!.querySelector('.card')!
    ).borderTopColor;

    expect(luminanceOver(border, surface)).toBeGreaterThan(
      luminanceOver(surface, surface)
    );
  }
);

it.each([
  ['dark', 0.15],
  ['light', 0.1],
])('gives a card the %s theme’s shadow', async (theme, alpha) => {
  document.body.innerHTML = `
    <div data-theme="${theme}">
      <craft-card>Body</craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const shadow = getComputedStyle(
    card.shadowRoot!.querySelector('.card')!
  ).boxShadow;

  expect(shadow).toContain(`rgba(0, 0, 0, ${alpha})`);
});

it('gives a selected uncolored card a dark header in the dark theme', async () => {
  document.body.innerHTML = `
    <div data-theme="dark">
      <span class="probe" style="background-color: var(--c-surface-default); color: var(--c-text-default)"></span>
      <craft-card active>
        <span slot="label">Header</span>
        Body
      </craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;

  const probe = getComputedStyle(document.querySelector('.probe')!);
  const header = getComputedStyle(
    card.shadowRoot!.querySelector('.card__header')!
  );

  expect(header.color).toBe(probe.color);
  expect(
    luminanceOver(header.backgroundColor, probe.backgroundColor)
  ).toBeLessThan(luminanceOver(probe.color, probe.backgroundColor) / 4);
});

it.each([['dark'], ['light']])(
  'gives a white card the same shadow as a white chip in the %s theme',
  async (theme) => {
    document.body.innerHTML = `
      <div data-theme="${theme}">
        <craft-card data-color="white">Card</craft-card>
        <craft-chip data-color="white">Chip</craft-chip>
      </div>`;
    const card = document.querySelector<CraftCard>('craft-card')!;
    const chip = document.querySelector('craft-chip')!;
    await Promise.all([card.updateComplete, chip.updateComplete]);

    expect(
      getComputedStyle(card.shadowRoot!.querySelector('.card')!).boxShadow
    ).toBe(
      getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!).boxShadow
    );
  }
);

it('keeps a black card’s body text readable on a light page, selected or not', async () => {
  document.body.innerHTML = `
    <div id="page" style="background: var(--c-surface-sunken)">
      <craft-card data-color="black">Body</craft-card>
      <craft-card data-color="black" active>Body</craft-card>
    </div>`;
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));
  const page = getComputedStyle(
    document.getElementById('page')!
  ).backgroundColor;

  for (const card of cards) {
    const style = getComputedStyle(card.shadowRoot!.querySelector('.card')!);
    const fill = luminanceOver(style.backgroundColor, page);
    const text = luminanceOver(style.color, page);
    const contrast =
      (Math.max(fill, text) + 0.05) / (Math.min(fill, text) + 0.05);

    expect(contrast).toBeGreaterThanOrEqual(4.5);
  }
});

it('pads its body with the container padding, or the same value without it', async () => {
  document.body.innerHTML = `
    <craft-card id="fallback">Body</craft-card>
    <div style="--cp-container-padding: 40px">
      <craft-card id="container">Body</craft-card>
    </div>
    <div id="spacing" style="padding: var(--c-spacing-lg)"></div>`;
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));
  const padding = (id: string) =>
    getComputedStyle(
      document.getElementById(id)!.shadowRoot!.querySelector('.card-body')!
    ).paddingInlineStart;

  expect(padding('fallback')).toBe(
    getComputedStyle(document.getElementById('spacing')!).paddingInlineStart
  );
  expect(padding('fallback')).not.toBe('0px');
  expect(padding('container')).toBe('40px');
});

it('gives a header the same height with or without actions', async () => {
  document.body.innerHTML = `
    <craft-card id="label" label="Card label">Body</craft-card>
    <craft-card id="actions" label="Card label">
      <craft-button slot="actions" size="small" icon="ellipsis" label="Actions"></craft-button>
      Body
    </craft-card>`;
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));
  const height = (id: string) =>
    document
      .getElementById(id)!
      .shadowRoot!.querySelector('.card__header')!
      .getBoundingClientRect().height;

  expect(height('label')).toBe(height('actions'));
});

it.each(['light', 'dark'])(
  'colors a header checkbox like the header’s other controls in the %s theme',
  async (theme) => {
    document.body.innerHTML = `
      <div data-theme="${theme}">
        <craft-card data-color="indigo">
          <div slot="header">
            <craft-checkbox label-sr-only><label slot="label">Select</label></craft-checkbox>
            <craft-button variant="plain" inherit icon="edit" aria-label="Edit"></craft-button>
          </div>
        </craft-card>
      </div>`;
    const card = document.querySelector<CraftCard>('craft-card')!;
    await card.updateComplete;
    const input = card.querySelector<HTMLInputElement>(
      'craft-checkbox > input'
    )!;
    const header = () =>
      getComputedStyle(card.shadowRoot!.querySelector('.card__header')!);
    const control = () =>
      getComputedStyle(card.querySelector('craft-button')!).color;
    const box = () => getComputedStyle(input);
    const mark = () => getComputedStyle(input, '::before').backgroundColor;

    expect(box().borderTopColor).toBe(control());

    input.checked = true;
    expect(box().backgroundColor).toBe(control());
    expect(mark()).toBe(header().backgroundColor);
    expect(contrast(mark(), box().backgroundColor)).toBeGreaterThanOrEqual(3);

    card.setAttribute('active', '');
    await card.updateComplete;

    expect(box().backgroundColor).toBe(control());
    expect(mark()).toBe(header().backgroundColor);
    expect(contrast(mark(), box().backgroundColor)).toBeGreaterThanOrEqual(3);
  }
);

it('keeps the accent color for a white card’s checked checkbox', async () => {
  document.body.innerHTML = `
    <craft-card data-color="white">
      <div slot="header">
        <craft-checkbox label-sr-only checked><label slot="label">Select</label></craft-checkbox>
      </div>
    </craft-card>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;
  const input = card.querySelector<HTMLInputElement>('craft-checkbox > input')!;
  input.checked = true;
  const reference = document.createElement('span');
  reference.style.color = 'var(--c-color-accent-fill-loud)';
  document.body.append(reference);

  expect(getComputedStyle(input).backgroundColor).toBe(
    getComputedStyle(reference).color
  );
});

it('gives neutral buttons in a colored card’s body the card’s color', async () => {
  document.body.innerHTML = `
    <craft-card data-color="cyan">
      <div class="card-body">
        <craft-button variant="plain" size="small">+5</craft-button>
      </div>
    </craft-card>
    <craft-card data-color="white">
      <craft-button variant="plain" size="small">+5</craft-button>
    </craft-card>
  `;
  const [cyan, white] = [...document.querySelectorAll<CraftCard>('craft-card')];
  await cyan!.updateComplete;
  const neutralFill = (button: Element) =>
    getComputedStyle(button).getPropertyValue('--c-color-neutral-fill-quiet');
  const button = cyan!.querySelector('craft-button')!;

  // It reads like the card's text, and hovers in the card's color.
  expect(getComputedStyle(button).color).toBe(
    getComputedStyle(cyan!.querySelector('.card-body')!).color
  );
  expect(neutralFill(button)).toBe(
    getComputedStyle(cyan!).getPropertyValue('--c-color-fill-quiet')
  );
  // A white card's buttons keep the neutral palette.
  expect(neutralFill(white!.querySelector('craft-button')!)).toBe(
    getComputedStyle(document.documentElement).getPropertyValue(
      '--c-color-neutral-fill-quiet'
    )
  );
});

it('styles the buttons and badges in a black card as in the dark theme', async () => {
  const content = `<craft-button>Button</craft-button><craft-badge>Badge</craft-badge>`;
  document.body.innerHTML = `
    <div id="black"><craft-card data-color="black">${content}</craft-card></div>
    <div id="dark" data-theme="dark">${content}</div>
    <div id="light">${content}</div>`;
  for (const element of document.querySelectorAll<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-card, craft-button, craft-badge')) {
    await element.updateComplete;
  }
  const looks = (id: string) =>
    [...document.querySelectorAll(`#${id} :is(craft-button, craft-badge)`)].map(
      (element) => {
        // A button draws its surface on its host; a badge, inside.
        const inner = element.shadowRoot!.firstElementChild!;
        const style = getComputedStyle(
          element.localName === 'craft-button'
            ? element
            : inner instanceof HTMLStyleElement
              ? inner.nextElementSibling!
              : inner
        );

        // Text color is left out: it's inherited, and a card sets its own.
        return [
          getComputedStyle(element).colorScheme,
          style.backgroundColor,
          style.borderTopColor,
        ];
      }
    );

  expect(looks('black')).toEqual(looks('dark'));
  expect(looks('black')).not.toEqual(looks('light'));
});

it('gives what’s in a colored card, or a card in a colored wrapper, the card’s color, except for white, gray, and black', async () => {
  document.body.innerHTML = `
    <div data-color="orange"><craft-card><span id="wrapped"></span></craft-card></div>
    <craft-card data-color="orange"><span id="colored"></span></craft-card>
    <craft-card><span id="plain"></span></craft-card>
    <craft-card data-color="gray"><span id="gray"></span></craft-card>
    <div data-color="white"><craft-card><span id="white"></span></craft-card></div>
    <span id="outside"></span>`;
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));
  const resolve = (id: string, value: string) => {
    const element = document.getElementById(id)!;
    element.style.color = value;
    return getComputedStyle(element).color;
  };
  const tokens = {
    '--c-text-default': '--c-color-orange-on-quiet',
    '--c-input-border-color': '--c-color-orange-border-normal',
    '--c-tab-border-active': '--c-color-orange-on-quiet',
    '--c-color-neutral-border-quiet': '--c-color-orange-border-quiet',
    '--c-color-neutral-fill-normal': '--c-color-orange-fill-normal',
  };

  for (const [token, orange] of Object.entries(tokens)) {
    for (const id of ['wrapped', 'colored']) {
      expect(resolve(id, `var(${token})`), `${id} ${token}`).toBe(
        resolve(id, `var(${orange})`)
      );
    }
  }

  for (const id of ['plain', 'gray', 'white']) {
    expect(resolve(id, 'var(--c-text-default)'), id).toBe(
      resolve('outside', 'var(--c-text-default)')
    );
  }
});
