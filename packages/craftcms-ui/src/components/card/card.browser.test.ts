import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import '../../styles/cp.css';
import './card.js';
import '../chip/chip.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../badge/badge.js';
import '../truncate/truncate.js';
import {contrast} from '../../../test/contrast';
import {
  HARD_IMAGES,
  MANY_ATTRIBUTES,
  type CoverImage,
} from '../../../test/cover-fixtures';
import type CraftCard from './card.js';
import type {Luminance} from './cover.js';

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
    <craft-card data-color="red">Red</craft-card>`;
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

it('gives a white card the white surface and text of the dark theme', async () => {
  document.body.innerHTML = `
    <div data-theme="dark">
      <span class="probe" style="background-color: var(--c-surface-white); color: var(--c-text-default)"></span>
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

it.each([
  ['light', ''],
  ['light', 'data-color="indigo"'],
  ['light', 'data-color="white"'],
  ['dark', ''],
])(
  'keeps a hovered header control readable on a selected card in the %s theme (%s)',
  async (theme, color) => {
    document.body.innerHTML = `
      <div data-theme="${theme}">
        <craft-card ${color} active>
          <div slot="header">
            <craft-button variant="plain" inherit icon="ellipsis" aria-label="Actions"></craft-button>
          </div>
        </craft-card>
      </div>`;
    const card = document.querySelector<CraftCard>('craft-card')!;
    await card.updateComplete;
    const button = card.querySelector('craft-button')!;

    await userEvent.hover(button);
    const style = getComputedStyle(button);

    expect(contrast(style.color, style.backgroundColor)).toBeGreaterThanOrEqual(
      3
    );
    await userEvent.unhover(button);
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

it('gives what’s in a tinted card the card’s color, but not in a neutral one', async () => {
  document.body.innerHTML = `
    <craft-card data-color="orange"><span id="colored"></span></craft-card>
    <craft-card><span id="plain"></span></craft-card>
    <craft-card data-color="gray"><span id="gray"></span></craft-card>
    <craft-card data-color="slate"><span id="slate"></span></craft-card>
    <craft-card data-color="white"><span id="white"></span></craft-card>
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
    '--c-form-control-border-color': '--c-color-orange-border-normal',
    '--c-tab-border-active': '--c-color-orange-on-quiet',
    '--c-color-neutral-border-quiet': '--c-color-orange-border-quiet',
    '--c-color-neutral-fill-normal': '--c-color-orange-fill-normal',
  };

  for (const [token, orange] of Object.entries(tokens)) {
    expect(resolve('colored', `var(${token})`), token).toBe(
      resolve('colored', `var(${orange})`)
    );
  }

  for (const id of ['plain', 'gray', 'slate', 'white']) {
    expect(resolve(id, 'var(--c-text-default)'), id).toBe(
      resolve('outside', 'var(--c-text-default)')
    );
  }
});

it('keeps a neutral card nested in a tinted one, and what’s in it, neutral', async () => {
  document.body.innerHTML = `
    <craft-card data-color="orange">
      <div>
        <craft-card data-color="white" id="white"><span id="in-white"></span></craft-card>
        <craft-card data-color="gray" id="gray"><span id="in-gray"></span></craft-card>
        <craft-card data-color="slate" id="slate"><span id="in-slate"></span></craft-card>
      </div>
    </craft-card>
    <craft-card data-color="white" id="top-white"><span id="in-top-white"></span></craft-card>
    <span id="outside"></span>`;
  const cards = [...document.querySelectorAll<CraftCard>('craft-card')];
  await Promise.all(cards.map((card) => card.updateComplete));
  const resolve = (id: string, value: string) => {
    const element = document.getElementById(id)!;
    element.style.color = value;
    return getComputedStyle(element).color;
  };
  const tokens = [
    '--c-text-default',
    '--c-text-quiet',
    '--c-form-control-border-color',
    '--c-color-neutral-border-quiet',
    '--c-color-neutral-fill-normal',
  ];

  for (const id of ['in-white', 'in-gray', 'in-slate']) {
    for (const token of tokens) {
      expect(resolve(id, `var(${token})`), `${id} ${token}`).toBe(
        resolve('outside', `var(${token})`)
      );
    }
    expect(resolve(id, 'var(--c-tab-border-active, red)'), id).toBe(
      resolve('outside', 'var(--c-tab-border-active, red)')
    );
  }

  // The white card's own text and border match a white card's outside.
  const look = (id: string) => {
    const style = getComputedStyle(
      document.getElementById(id)!.shadowRoot!.querySelector('.card')!
    );
    return [style.color, style.borderTopColor];
  };
  expect(look('white')).toEqual(look('top-white'));
});

it('keeps quiet text and form controls legible on a white card in the dark theme', async () => {
  document.body.innerHTML = `
    <div data-theme="dark">
      <craft-card data-color="white">
        <span class="quiet" style="color: var(--c-text-quiet)">Instructions</span>
        <span class="control" style="color: var(--c-form-control-border-color)">Input</span>
      </craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  await card.updateComplete;
  const fill = getComputedStyle(
    card.shadowRoot!.querySelector('.card')!
  ).backgroundColor;
  const colorOf = (selector: string) =>
    getComputedStyle(document.querySelector(selector)!).color;

  expect(contrast(colorOf('.quiet'), fill)).toBeGreaterThanOrEqual(4.5);
  expect(contrast(colorOf('.control'), fill)).toBeGreaterThanOrEqual(3);
});

const GRAY_IMAGE = `data:image/svg+xml,${encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="6"><rect width="8" height="6" fill="#777"/></svg>'
)}`;

const GRAY_LUMINANCE: Luminance = [[[0.184, 0.184]]];

async function coverCard(label = ''): Promise<CraftCard> {
  document.body.innerHTML = `
    <craft-card cover-src="${GRAY_IMAGE}" style="inline-size: 320px">
      <div slot="header" style="display: flex; justify-content: space-between; inline-size: 100%">
        <div class="start" style="display: flex; gap: 8px">
          <craft-checkbox label-sr-only><label slot="label">Select</label></craft-checkbox>
          ${label ? `<span data-card-label>${label}</span>` : ''}
        </div>
        <craft-button icon="ellipsis" variant="plain" inherit size="small" flush aria-label="Actions"></craft-button>
      </div>
      <a href="#">Photo</a>
      <div slot="thumbnail">Thumbnail</div>
    </craft-card>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  card.luminance = GRAY_LUMINANCE;
  await card.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  await card.updateComplete;

  return card;
}

it('keeps a cover card’s header controls in place when its header gets a label bar', async () => {
  const card = await coverCard('Photo');
  const header = card.querySelector('[slot="header"]')!;
  const label = card.querySelector('[data-card-label]')!;
  const positions = () =>
    [
      card.shadowRoot!.querySelector('.card__header')!,
      card.querySelector('craft-checkbox > input')!,
      label,
      card.querySelector('craft-button')!,
    ].map((element) => {
      const {x, y, width, height} = element.getBoundingClientRect();
      return [x, y, width, height];
    });

  const bar = positions();
  label.removeAttribute('data-card-label');
  card.requestUpdate();
  await card.updateComplete;

  expect(header.getAttribute('data-cover')).toBe('backed');
  expect(positions()).toEqual(bar);
});

it('themes a cover card’s header and body, and leaves its thumbnail alone', async () => {
  const card = await coverCard();
  const theme = (selector: string) =>
    card.querySelector(selector)!.getAttribute('data-theme');

  expect([
    theme('[slot="header"]'),
    theme('a'),
    theme('[slot="thumbnail"]'),
  ]).toEqual(['dark', 'dark', null]);

  card.removeAttribute('cover-src');
  await card.updateComplete;

  expect([theme('[slot="header"]'), theme('a')]).toEqual([null, null]);
});

it('ignores a cover card’s color', async () => {
  const card = await coverCard();
  const border = () =>
    getComputedStyle(card.shadowRoot!.querySelector('.card')!, '::after')
      .boxShadow;
  const neutral = border();

  card.setAttribute('data-color', 'violet');
  await card.updateComplete;

  expect(border()).toBe(neutral);
});

it('keeps a cover card within its width when its content has a long title', async () => {
  const card = await coverCard();
  // In a card grid's column, as element indexes lay cards out, with a title
  // that's cut off with an ellipsis rather than wrapped, as element cards' are.
  const grid = document.createElement('div');
  grid.style.cssText = 'display: grid; grid-template-columns: 279px';
  card.style.removeProperty('inline-size');
  card.before(grid);
  grid.append(card);
  card.querySelector('a')!.outerHTML =
    `<craft-truncate><a href="#" style="white-space: nowrap">${'The Course of Empire: '.repeat(6)}</a></craft-truncate>`;
  await customElements.whenDefined('craft-truncate');
  await card.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  expect(
    card.shadowRoot!.querySelector('.card')!.getBoundingClientRect().width
  ).toBeCloseTo(279, 0);
});

it('backs a cover card’s plain buttons more strongly when they’re hovered', async () => {
  const card = await coverCard();
  const button = card.querySelector('craft-button')!;
  const resting = getComputedStyle(button).backgroundColor;

  await userEvent.hover(button);
  await expect
    .poll(() => getComputedStyle(button).backgroundColor)
    .toBe('rgba(0, 0, 0, 0.8)');

  expect(
    contrast(getComputedStyle(button).color, resting, '#777')
  ).toBeLessThan(
    contrast(
      getComputedStyle(button).color,
      getComputedStyle(button).backgroundColor,
      '#777'
    )
  );
});

it.each([
  ['checkbox', 'craft-checkbox > input'],
  ['action menu', 'craft-button'],
  ['title link', 'a'],
])(
  'gives a cover card’s focused %s a black and white ring inside the card',
  async (_, selector) => {
    const card = await coverCard();
    const target = card.querySelector<HTMLElement>(selector)!;

    for (let i = 0; i < 5 && document.activeElement !== target; i++) {
      await userEvent.keyboard('{Tab}');
    }

    const style = getComputedStyle(target);
    const rings = [
      style.outlineColor,
      style.boxShadow.match(/rgba?\([^)]+\)/)![0],
    ];
    const ring = target.getBoundingClientRect();
    const reach =
      parseFloat(style.outlineOffset) + parseFloat(style.outlineWidth);
    const bounds = card
      .shadowRoot!.querySelector('.card')!
      .getBoundingClientRect();

    expect(rings.sort()).toEqual(['rgb(0, 0, 0)', 'rgb(255, 255, 255)']);
    expect(ring.left - reach).toBeGreaterThanOrEqual(bounds.left);
    expect(ring.right + reach).toBeLessThanOrEqual(bounds.right);
    expect(ring.top - reach).toBeGreaterThanOrEqual(bounds.top);
    expect(ring.bottom + reach).toBeLessThanOrEqual(bounds.bottom);
  }
);

it('falls back to a normal card when its cover image fails to load', async () => {
  const card = await coverCard();

  card.setAttribute('cover-src', 'data:image/png;base64,bm90IGFuIGltYWdl');

  await expect
    .poll(() => card.shadowRoot!.querySelector('.card--cover'))
    .toBeNull();
  await card.updateComplete;
  expect(
    card.querySelector('[slot="header"]')!.hasAttribute('data-cover')
  ).toBe(false);
  expect(
    card.shadowRoot!.querySelector<HTMLElement>('[part="thumbnail"]')!.hidden
  ).toBe(false);
});

const LONG_TEXT =
  'The Course of Empire: Destruction, by Thomas Cole, oil on canvas, 1836';

const coverHeader = (label = '') => `
  <div slot="header" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; inline-size: 100%">
    <div style="display: flex; align-items: center; gap: 8px; min-inline-size: 0">
      <craft-checkbox label-sr-only><label slot="label">Select</label></craft-checkbox>
      ${label ? `<span data-card-label>${label}</span>` : ''}
    </div>
    <craft-button icon="ellipsis" variant="plain" inherit size="small" flush aria-label="Actions"></craft-button>
  </div>`;

/** Cover cards with each kind of header, and content short, long, and cut off. */
const COVER_CONTENTS = [
  coverHeader() + '<a href="#">Photo</a><div>photo.jpg · 800×600</div>',
  coverHeader(LONG_TEXT) +
    `<a href="#">${LONG_TEXT}</a><div>${LONG_TEXT}</div><div slot="footer">${LONG_TEXT}</div>`,
  coverHeader('Photo') +
    `<craft-truncate><a href="#" style="white-space: nowrap">${'A'.repeat(120)}</a></craft-truncate><div>${LONG_TEXT}</div>`,
  `<craft-badge slot="badge">Live</craft-badge>
    <craft-button slot="actions" icon="ellipsis" variant="plain" inherit size="small" aria-label="Actions"></craft-button>
    <a href="#">Photo</a>`,
  coverHeader('Photo') + '<a href="#">Photo</a>' + MANY_ATTRIBUTES,
];

const TEXT_SPACING = `
  craft-card * {
    line-height: 1.5 !important;
    letter-spacing: 0.12em !important;
    word-spacing: 0.16em !important;
  }
  craft-card p { margin-block-end: 2em !important; }`;

const injected: HTMLStyleElement[] = [];

function inject(css: string) {
  const style = document.createElement('style');
  style.textContent = css;
  document.head.append(style);
  injected.push(style);
}

afterEach(async () => {
  await page.viewport(414, 896);
  injected.splice(0).forEach((style) => style.remove());
  document.documentElement.style.removeProperty('font-size');
});

async function settle(cards: CraftCard[]) {
  await Promise.all(cards.map((card) => card.updateComplete));
  await new Promise((resolve) => setTimeout(resolve));
  await Promise.all(cards.map((card) => card.updateComplete));
  await new Promise((resolve) => requestAnimationFrame(resolve));
}

const HEADER_SLOTS = ['header', 'label', 'badge', 'actions'];

/** The rects of each line of visible text in an element, as it wraps. */
function lines(element: Element): {rect: DOMRect; node: Text}[] {
  const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
  const found: {rect: DOMRect; node: Text}[] = [];

  for (let node = walker.nextNode(); node; node = walker.nextNode()) {
    if (
      !node.textContent!.trim() ||
      node.parentElement!.closest('craft-checkbox')
    ) {
      continue;
    }

    const range = document.createRange();
    range.selectNodeContents(node);

    for (const rect of Array.from(range.getClientRects())) {
      if (rect.width && rect.height) {
        found.push({rect, node: node as Text});
      }
    }
  }

  return found;
}

const SIZES = ['100%', '200%', '400%'];

it.each(
  [260, 279, 288].flatMap((width) =>
    SIZES.flatMap((size) =>
      [false, true].map((spacing) => [width, size, spacing] as const)
    )
  )
)(
  'keeps every line of a cover card’s text and its controls where its fill is at full strength (%ipx wide, %s text, text spacing %s)',
  async (width, size, spacing) => {
    document.documentElement.style.fontSize = size;
    if (spacing) {
      inject(TEXT_SPACING);
    }

    document.body.innerHTML = ['light', 'dark']
      .map(
        (theme) => `
          <div data-theme="${theme}" style="display: grid; grid-template-columns: ${width}px; gap: 16px">
            ${COVER_CONTENTS.flatMap((content) =>
              ['', 'active'].map(
                (active) =>
                  `<craft-card cover-src="${HARD_IMAGES[0]!.src}" ${active}>${content}</craft-card>`
              )
            ).join('')}
          </div>`
      )
      .join('');
    const cards = Array.from(
      document.querySelectorAll<CraftCard>('craft-card')
    );
    await customElements.whenDefined('craft-truncate');
    await settle(cards);

    const rem = parseFloat(getComputedStyle(document.documentElement).fontSize);

    for (const card of cards) {
      const base = card.shadowRoot!.querySelector('.card')!;
      const bounds = base.getBoundingClientRect();
      const header = card
        .shadowRoot!.querySelector('.card__header')!
        .getBoundingClientRect();
      const body = card.shadowRoot!.querySelector('.card-body')!;
      const fade =
        parseFloat(getComputedStyle(body).getPropertyValue('--_fade')) * rem;
      const scrim = body.getBoundingClientRect().top + fade;

      for (const child of Array.from(card.children)) {
        const found = lines(child);

        if (HEADER_SLOTS.includes(child.slot)) {
          if (found.length) {
            expect(child.getAttribute('data-cover')).toBe('bar');
          }
          for (const {rect} of found) {
            expect(rect.top).toBeGreaterThanOrEqual(header.top - 0.5);
            expect(rect.bottom).toBeLessThanOrEqual(header.bottom + 0.5);
          }
        } else {
          for (const {rect} of found) {
            expect(rect.top).toBeGreaterThanOrEqual(scrim - 0.5);
          }
        }
      }

      for (const control of Array.from(
        card.querySelectorAll(
          'craft-checkbox > input, [data-cover="backed"] craft-button'
        )
      )) {
        const rect = control.getBoundingClientRect();

        expect(rect.top).toBeGreaterThanOrEqual(bounds.top);
        expect(rect.bottom).toBeLessThanOrEqual(header.bottom);
        if (control.closest('[data-cover="backed"]')) {
          expect(getComputedStyle(control).backgroundColor).not.toBe(
            'rgba(0, 0, 0, 0)'
          );
        }
      }
    }
  }
);

/** Decodes a screenshot of `element`, to read the pixels in its boxes. */
async function screenshot(element: Element) {
  const base64 = (await page.screenshot({element, save: false})) as string;
  const image = new Image();
  image.src = `data:image/png;base64,${base64}`;
  await image.decode();

  const canvas = document.createElement('canvas');
  canvas.width = image.naturalWidth;
  canvas.height = image.naturalHeight;
  const context = canvas.getContext('2d', {willReadFrequently: true})!;
  context.drawImage(image, 0, 0);
  const origin = element.getBoundingClientRect();
  const scale = image.naturalWidth / origin.width;

  /** The colors in `box`, leaving out its rounded corners. */
  return (box: DOMRect, inflate = 0, radius = 0): string[] => {
    const left = Math.ceil((box.left - inflate - origin.left) * scale);
    const top = Math.ceil((box.top - inflate - origin.top) * scale);
    const width = Math.max(
      1,
      Math.floor((box.right + inflate - origin.left) * scale) - left
    );
    const height = Math.max(
      1,
      Math.floor((box.bottom + inflate - origin.top) * scale) - top
    );
    const corner = Math.ceil((radius + inflate) * scale);
    const {data} = context.getImageData(left, top, width, height);
    const colors = new Set<string>();

    for (let y = 0; y < height; y++) {
      for (let x = 0; x < width; x++) {
        const inCornerX = x < corner || x >= width - corner;
        const inCornerY = y < corner || y >= height - corner;
        if (inCornerX && inCornerY) {
          continue;
        }

        const i = (y * width + x) * 4;
        colors.add(`rgb(${data[i]}, ${data[i + 1]}, ${data[i + 2]})`);
      }
    }

    return Array.from(colors);
  };
}

const worst = (ink: string, colors: string[]) =>
  Math.min(...colors.map((color) => contrast(ink, color)));

const HIDE_INK = `
  craft-card > *, craft-card > * * {
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
    text-decoration-color: transparent !important;
    --c-checkbox-border-color: transparent !important;
    transition: none !important;
  }`;

interface Shortfall {
  text: string;
  ratio: number;
}

/**
 * Screenshots a cover card with its ink hidden, and returns every line of text
 * under 4.5:1 and every header control under 3:1 against the pixels behind it.
 * Only what's visible is measured, and lines the card cuts off entirely are
 * listed as `clipped`. Badges are left out: they have their own fill, and
 * their own tests.
 */
async function coverContrast(
  card: CraftCard
): Promise<{shortfalls: Shortfall[]; clipped: string[]}> {
  const bounds = card
    .shadowRoot!.querySelector('.card')!
    .getBoundingClientRect();
  const visible = (rect: DOMRect) => {
    const left = Math.max(rect.left, bounds.left + 1);
    const top = Math.max(rect.top, bounds.top + 1);
    const right = Math.min(rect.right, bounds.right - 1);
    const bottom = Math.min(rect.bottom, bounds.bottom - 1);

    return right > left && bottom > top
      ? new DOMRect(left, top, right - left, bottom - top)
      : null;
  };

  const clipped: string[] = [];
  const text = Array.from(card.children).flatMap((child) =>
    lines(child).flatMap(({rect, node}) => {
      if (node.parentElement!.closest('craft-badge')) {
        return [];
      }

      const shown = visible(rect);

      if (!shown) {
        clipped.push(node.textContent!.trim());
      }

      return shown
        ? [
            {
              rect: shown,
              text: node.textContent!.trim(),
              // Text slotted into a component inherits from its slot, not its host.
              ink: getComputedStyle(node.assignedSlot ?? node.parentElement!)
                .color,
            },
          ]
        : [];
    })
  );
  const controls = Array.from(
    card.querySelectorAll(
      ':is([slot="header"], [slot="actions"]) :is(craft-checkbox > input, craft-button), [slot="actions"]:is(craft-button)'
    )
  ).map((control) => {
    const style = getComputedStyle(control);
    const checkbox = control.matches('input');

    // A pixel in from its edge, past the anti-aliasing against the image.
    return {
      rect: control.getBoundingClientRect(),
      text: checkbox ? 'checkbox' : 'action menu',
      ink: checkbox ? style.borderTopColor : style.color,
      radius: parseFloat(style.borderTopLeftRadius),
    };
  });

  inject(HIDE_INK);
  await Promise.all(
    document.getAnimations().map((animation) => animation.finished)
  );
  await new Promise((resolve) => requestAnimationFrame(resolve));
  const pixels = await screenshot(card);

  expect(text.length).toBeGreaterThan(0);

  const shortfalls = [
    ...text.map(({rect, text, ink}) => ({
      text,
      ratio: worst(ink, pixels(rect)),
      target: 4.5,
    })),
    ...controls.map(({rect, text, ink, radius}) => ({
      text,
      ratio: worst(ink, pixels(rect, -1, radius)),
      target: 3,
    })),
  ]
    .filter(({ratio, target}) => ratio < target)
    .map(({text, ratio}) => ({text, ratio: Math.round(ratio * 100) / 100}));

  return {shortfalls, clipped};
}

async function renderCover(
  image: CoverImage,
  content: string,
  width = 279
): Promise<CraftCard> {
  if (image.name === 'Not loaded') {
    inject('craft-card::part(cover) { visibility: hidden; }');
  }

  document.body.innerHTML = `
    <div style="inline-size: ${width}px">
      <craft-card cover-src="${image.src}">${content}</craft-card>
    </div>`;
  const card = document.querySelector<CraftCard>('craft-card')!;
  card.luminance = image.luminance;
  await settle([card]);
  await card
    .shadowRoot!.querySelector<HTMLImageElement>('.card__cover')!
    .decode();

  return card;
}

const COVER_IMAGES = [...HARD_IMAGES, {...HARD_IMAGES[3]!, name: 'Not loaded'}];

it.each(
  COVER_IMAGES.flatMap((image) =>
    [false, true].map((labeled) => [image.name, labeled, image] as const)
  )
)(
  'gives every pixel behind a cover card’s text 4.5:1, and behind its controls 3:1 (%s, label %s)',
  async (_, labeled, image) => {
    const card = await renderCover(
      image,
      `${coverHeader(labeled ? LONG_TEXT : '')}
        <a href="#">${LONG_TEXT}</a>
        <div>photo.jpg · 800×600</div>`
    );

    expect((await coverContrast(card)).shortfalls).toEqual([]);
  }
);

it.each(COVER_IMAGES.map((image) => [image.name, image] as const))(
  'gives every pixel behind a cover card’s attributes 4.5:1 (%s)',
  async (_, image) => {
    await page.viewport(1024, 900);
    const card = await renderCover(
      image,
      `${coverHeader('The Quick Brown Fox')}
        <a href="#">The Quick Brown Fox</a>
        ${MANY_ATTRIBUTES}`,
      600
    );

    expect(await coverContrast(card)).toEqual({shortfalls: [], clipped: []});
  }
);
