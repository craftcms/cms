import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {cdp, userEvent} from 'vite-plus/test/browser';
import type CraftRadio from '../radio/radio.js';
import type CraftRadioGroup from './radio-group.js';
import '../../styles/cp.css';
import '../icon/icon.js';
import '../radio/radio.js';
import './radio-group.js';
import {contrast} from '../../../test/contrast';
import {
  PALETTE_SAMPLE,
  VIEW_MODES,
  type RadioCardOption,
} from '../../../test/radio-card-fixtures';

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

const settle = () => new Promise((resolve) => setTimeout(resolve));

/** Moves the pointer to an empty corner, so nothing it was left over stays hovered. */
async function parkPointer(): Promise<void> {
  const spot = document.createElement('div');
  spot.style.cssText =
    'position: fixed; inset-block-end: 0; inset-inline-end: 0; width: 10px; height: 10px;';
  document.body.append(spot);
  await userEvent.hover(spot);
  spot.remove();
}

async function render(
  appearance = 'button-group',
  width = ''
): Promise<{
  group: CraftRadioGroup;
  radios: CraftRadio[];
  inputs: HTMLInputElement[];
}> {
  document.body.innerHTML = `
    <craft-radio-group appearance="${appearance}" label="Position" style="--c-button-radius: 4px;${width ? ` inline-size: ${width}` : ''}">
      ${['start', 'center', 'end']
        .map(
          (value) => `
        <div>
          <craft-radio>
            <input slot="input" type="radio" id="position-${value}" name="position" value="${value}"${value === 'center' ? ' checked' : ''}>
            <label slot="label" for="position-${value}">${value}</label>
          </craft-radio>
        </div>`
        )
        .join('')}
    </craft-radio-group>`;
  const group = document.querySelector('craft-radio-group')!;
  const radios = Array.from(document.querySelectorAll('craft-radio'));
  await Promise.all([
    group.updateComplete,
    ...radios.map((radio) => radio.updateComplete),
  ]);
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await settle();

  return {
    group,
    radios,
    inputs: radios.map((radio) => radio.querySelector('input')!),
  };
}

it('moves the selection with the arrow keys', async () => {
  const {group, inputs} = await render();

  await userEvent.tab();
  expect(document.activeElement).toBe(inputs[1]);

  await userEvent.keyboard('{ArrowRight}');
  await settle();
  expect(document.activeElement).toBe(inputs[2]);
  expect(group.value).toBe('end');

  await userEvent.tab();
  expect(inputs).not.toContain(document.activeElement);
});

it.each([
  ['share a name', ' name="size"', ' name="color"'],
  ['have no name', '', ''],
])(
  'makes each group one Tab stop whose arrow keys select its next enabled option, when its radios %s',
  async (_, sizeName, colorName) => {
    document.body.innerHTML = `
      <button>Before</button>
      <craft-radio-group label="Size"${sizeName}>
        <craft-radio value="s" label="Small"></craft-radio>
        <craft-radio value="m" label="Medium" disabled></craft-radio>
        <craft-radio value="l" label="Large" checked></craft-radio>
      </craft-radio-group>
      <craft-radio-group label="Color"${colorName}>
        <craft-radio value="r" label="Red"></craft-radio>
        <craft-radio value="g" label="Green"></craft-radio>
      </craft-radio-group>
      <button>After</button>`;
    const [size, color] = document.querySelectorAll('craft-radio-group');
    const [before, after] = document.querySelectorAll('button');
    await Promise.all([size!.updateComplete, color!.updateComplete]);
    await settle();
    const [s, , l, r, g] = document.querySelectorAll('input');
    before!.focus();

    await userEvent.tab();
    expect(document.activeElement).toBe(l);

    await userEvent.keyboard('{ArrowDown}');
    expect(document.activeElement).toBe(s);
    expect(size!.value).toBe('s');

    await userEvent.keyboard('{ArrowRight}');
    expect(document.activeElement).toBe(l);
    expect(size!.value).toBe('l');

    await userEvent.tab();
    expect(document.activeElement).toBe(r);
    expect(color!.value).toBe('');

    await userEvent.keyboard(' ');
    expect(color!.value).toBe('r');

    await userEvent.keyboard('{ArrowUp}');
    expect(document.activeElement).toBe(g);
    expect([size!.value, color!.value]).toEqual(['l', 'g']);

    await userEvent.tab();
    expect(document.activeElement).toBe(after);

    await settle();
    await userEvent.tab({shift: true});
    expect(document.activeElement).toBe(g);
  }
);

it('draws the focus ring around the focused button', async () => {
  const {radios, inputs} = await render();

  await userEvent.tab();
  await settle();

  const ring = inputs[1]!.getBoundingClientRect();
  const button = radios[1]!.getBoundingClientRect();
  const style = getComputedStyle(inputs[1]!);

  expect(style.outlineStyle).toBe('solid');
  expect(parseFloat(style.outlineWidth)).toBeGreaterThan(0);
  expect(ring.width).toBeCloseTo(button.width - 2);
  expect(ring.height).toBeCloseTo(button.height - 2);
  expect(Number(getComputedStyle(radios[1]!).zIndex)).toBeGreaterThan(
    Number(getComputedStyle(radios[0]!).zIndex) || 0
  );
});

it('selects an option when its button is clicked', async () => {
  const {group, radios} = await render();

  await userEvent.click(radios[0]!, {position: {x: 2, y: 2}});
  await parkPointer();
  await settle();

  expect(group.value).toBe('start');
});

it('joins the buttons, rounding only the outer corners', async () => {
  const {radios} = await render();
  const [first, middle, last] = radios.map((radio) =>
    radio.getBoundingClientRect()
  );
  const radius = (radio: CraftRadio, corner: string) =>
    parseFloat(getComputedStyle(radio).getPropertyValue(corner));

  expect(middle!.left).toBeCloseTo(first!.right - 1);
  expect(last!.left).toBeCloseTo(middle!.right - 1);
  expect(middle!.top).toBeCloseTo(first!.top);
  expect(radius(radios[0]!, 'border-start-start-radius')).toBe(4);
  expect(radius(radios[0]!, 'border-start-end-radius')).toBe(0);
  expect(radius(radios[1]!, 'border-start-start-radius')).toBe(0);
  expect(radius(radios[1]!, 'border-start-end-radius')).toBe(0);
  expect(radius(radios[2]!, 'border-start-end-radius')).toBe(4);
});

it('fills the selected button and raises it', async () => {
  const {radios} = await render();
  const [, selected, unselected] = radios.map((radio) =>
    getComputedStyle(radio)
  );

  expect(selected!.backgroundColor).not.toBe(unselected!.backgroundColor);
  expect(selected!.color).not.toBe(unselected!.color);
  expect(Number(selected!.zIndex)).toBeGreaterThan(
    Number(unselected!.zIndex) || 0
  );
});

it('makes an icon-only option a square button', async () => {
  document.body.innerHTML = `
    <craft-radio-group appearance="button-group" label="Position">
      <craft-radio>
        <input slot="input" type="radio" id="corner" name="corner" value="top">
        <label slot="label" for="corner"><craft-icon name="arrow-up" label="Top"></craft-icon></label>
      </craft-radio>
    </craft-radio-group>`;
  const group = document.querySelector('craft-radio-group')!;
  const radio = document.querySelector('craft-radio')!;
  await group.updateComplete;
  await radio.updateComplete;
  await settle();

  const {width, height} = radio.getBoundingClientRect();

  expect(radio.hasAttribute('data-icon-only')).toBe(true);
  expect(width).toBeCloseTo(height);
  expect(height).toBeGreaterThanOrEqual(24);
});

it('distinguishes the selected button in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const {radios} = await render();

  expect(matchMedia('(forced-colors: active)').matches).toBe(true);

  const [, selected, unselected] = radios.map((radio) =>
    getComputedStyle(radio)
  );

  expect(selected!.backgroundColor).not.toBe(unselected!.backgroundColor);
  expect(selected!.color).not.toBe(unselected!.color);
});

it('draws lighter borders between buttons than around the group', async () => {
  const {radios} = await render();
  const [first, , last] = radios.map((radio) => getComputedStyle(radio));

  expect(first!.borderInlineEndColor).not.toBe(first!.borderInlineStartColor);
  expect(last!.borderInlineStartColor).toBe(first!.borderInlineEndColor);
  expect(last!.borderInlineEndColor).toBe(first!.borderInlineStartColor);
});

it('gives only the unchecked buttons a pointer cursor', async () => {
  const {inputs} = await render();

  expect(inputs.map((input) => getComputedStyle(input).cursor)).toEqual([
    'pointer',
    'default',
    'pointer',
  ]);
});

const cardBox = (radio: CraftRadio) =>
  radio.shadowRoot!.querySelector<HTMLElement>('[part="icon"]')!;

async function renderCards(
  width: string,
  wrap: (html: string) => string = (html) => html,
  appearance = 'card-group',
  options: RadioCardOption[] = VIEW_MODES
): Promise<{
  group: CraftRadioGroup;
  radios: CraftRadio[];
  inputs: HTMLInputElement[];
}> {
  document.body.innerHTML = wrap(`
    <craft-radio-group appearance="${appearance}" label="View Mode" ${width ? `style="inline-size: ${width}"` : ''}>
      ${options
        .map(
          (option) => `
        <div>
          <craft-radio>
            <span slot="icon" aria-hidden="true">${option.svg}</span>
            <input slot="input" type="radio" id="view-${option.value}" name="view" value="${option.value}"${option.value === 'cards' ? ' checked' : ''}>
            <label slot="label" for="view-${option.value}">${option.label}</label>
          </craft-radio>
        </div>`
        )
        .join('')}
    </craft-radio-group>`);
  const group = document.querySelector('craft-radio-group')!;
  const radios = Array.from(document.querySelectorAll('craft-radio'));
  await Promise.all([
    group.updateComplete,
    ...radios.map((radio) => radio.updateComplete),
  ]);
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await settle();

  return {
    group,
    radios,
    inputs: radios.map((radio) => radio.querySelector('input')!),
  };
}

it('joins the cards in one row, rounding only the outer corners', async () => {
  const {group, radios} = await renderCards('40rem');
  const rects = radios.map((radio) => cardBox(radio).getBoundingClientRect());
  const radius = (radio: CraftRadio, corner: string) =>
    parseFloat(getComputedStyle(cardBox(radio)).getPropertyValue(corner));

  expect(group.hasAttribute('data-wrapped')).toBe(false);
  rects.slice(1).forEach((rect, i) => {
    expect(rect.top).toBeCloseTo(rects[0]!.top);
    expect(rect.left).toBeCloseTo(rects[i]!.right - 1);
  });
  expect(radius(radios[0]!, 'border-start-start-radius')).toBeGreaterThan(0);
  expect(radius(radios[0]!, 'border-start-end-radius')).toBe(0);
  expect(radius(radios[2]!, 'border-start-start-radius')).toBe(0);
  expect(radius(radios[4]!, 'border-start-end-radius')).toBeGreaterThan(0);
});

it('wraps the cards into separate cards when the row does not fit', async () => {
  const {group, radios} = await renderCards('18rem');
  const tops = new Set(
    radios.map((radio) => Math.round(radio.getBoundingClientRect().top))
  );

  expect(group.hasAttribute('data-wrapped')).toBe(true);
  expect(tops.size).toBeGreaterThan(1);
  for (const radio of radios) {
    const style = getComputedStyle(cardBox(radio));
    expect(parseFloat(style.borderStartStartRadius)).toBeGreaterThan(0);
    expect(parseFloat(style.borderEndEndRadius)).toBeGreaterThan(0);
  }
});

it('rejoins the cards when the group widens again', async () => {
  const {group} = await renderCards('18rem');

  group.style.inlineSize = '40rem';
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await settle();

  expect(group.hasAttribute('data-wrapped')).toBe(false);
});

it('fits the cards in a 320px wide container without scrolling sideways', async () => {
  const {group, radios} = await renderCards('320px');
  const {right} = group.getBoundingClientRect();

  for (const radio of radios) {
    expect(radio.getBoundingClientRect().right).toBeLessThanOrEqual(
      right + 0.5
    );
  }
});

it('lays wrapped cards out in the order the arrow keys follow', async () => {
  const {group, radios, inputs} = await renderCards('18rem');
  const visual = [...radios].sort((a, b) => {
    const ra = a.getBoundingClientRect();
    const rb = b.getBoundingClientRect();
    return Math.round(ra.top - rb.top) || ra.left - rb.left;
  });

  expect(visual).toEqual(radios);

  await userEvent.tab();
  expect(document.activeElement).toBe(inputs[1]);
  await userEvent.keyboard('{ArrowRight}');
  await settle();
  expect(group.value).toBe('cards-grid');
});

it('selects an option when its illustration is clicked', async () => {
  const {group, radios} = await renderCards('40rem');

  const box = radios[3]!
    .querySelector('[slot="icon"]')!
    .getBoundingClientRect();
  const host = radios[3]!.getBoundingClientRect();
  await userEvent.click(radios[3]!, {
    position: {
      x: box.left - host.left + box.width / 2,
      y: box.top - host.top + box.height / 2,
    },
  });
  await parkPointer();
  await settle();

  expect(group.value).toBe('blocks');
});

it('draws the focus ring around the focused card', async () => {
  const {radios} = await renderCards('40rem');

  await userEvent.tab();
  await settle();

  const style = getComputedStyle(cardBox(radios[1]!));
  expect(style.outlineStyle).toBe('solid');
  expect(parseFloat(style.outlineWidth)).toBeGreaterThan(0);
  expect(getComputedStyle(cardBox(radios[0]!)).outlineStyle).toBe('none');
});

it('marks the selected card with a thicker border and a bold label', async () => {
  const {radios} = await renderCards('40rem');
  const [selected, unselected] = [radios[1]!, radios[2]!].map((radio) =>
    getComputedStyle(cardBox(radio))
  );
  const weight = (radio: CraftRadio) =>
    Number(
      getComputedStyle(radio.shadowRoot!.querySelector('.choice-field__label')!)
        .fontWeight
    );

  expect(parseFloat(selected!.borderTopWidth)).toBeGreaterThan(
    parseFloat(unselected!.borderTopWidth)
  );
  expect(weight(radios[1]!)).toBeGreaterThan(weight(radios[2]!));
});

it('draws the selected illustration in blue, following the selection and the theme', async () => {
  const BLUE_500 = 'rgb(62, 116, 242)';
  const SLATE_500 = 'rgb(111, 124, 148)';
  const SLATE_500_DARK = 'rgb(143, 155, 175)';
  const {radios, inputs} = await renderCards('40rem');
  const hairline = (radio: CraftRadio) =>
    getComputedStyle(
      radio.querySelector(
        '[slot="icon"] [style*="stroke: var(--c-illustration-line)"]'
      )!
    ).stroke;

  expect(hairline(radios[1]!)).toBe(BLUE_500);
  expect(hairline(radios[2]!)).toBe(SLATE_500);

  await userEvent.click(inputs[2]!);
  await settle();
  expect(hairline(radios[2]!)).toBe(BLUE_500);
  expect(hairline(radios[1]!)).toBe(SLATE_500);

  document.documentElement.dataset.theme = 'dark';
  try {
    expect(hairline(radios[1]!)).toBe(SLATE_500_DARK);
  } finally {
    delete document.documentElement.dataset.theme;
  }
});

it("keeps the illustrations' outlines, figures and marks at 3:1 at rest, hovered and selected, in both themes", async () => {
  const failures: string[] = [];

  for (const theme of ['light', 'dark']) {
    const {radios, inputs} = await renderCards(
      '40rem',
      (html) =>
        `<div data-theme="${theme}" style="background-color: var(--c-surface-default)">${html}</div>`,
      'card-group',
      [VIEW_MODES[1]!, PALETTE_SAMPLE]
    );
    const sample = radios[1]!;
    const icon = sample.querySelector('[slot="icon"]')!;
    const paint = (selector: string, property: 'fill' | 'stroke') =>
      getComputedStyle(icon.querySelector(selector)!)[property];

    const check = (state: string) => {
      const backdrop = getComputedStyle(
        document.querySelector('[data-theme]')!
      ).backgroundColor;
      const card = getComputedStyle(cardBox(sample)).backgroundColor;
      const field = paint(
        '[style*="fill: var(--c-illustration-field)"]',
        'fill'
      );
      const figure = paint(
        '[style*="fill: var(--c-illustration-figure)"]',
        'fill'
      );
      const mark = paint(
        '[style*="stroke: var(--c-illustration-mark)"]',
        'stroke'
      );
      const pairs: [string, string, string][] = [
        [
          'outline / card',
          paint('[style*="stroke: var(--c-illustration-line)"]', 'stroke'),
          card,
        ],
        [
          'outline / surface',
          paint('[style*="fill: var(--c-illustration-surface)"]', 'stroke'),
          paint('[style*="fill: var(--c-illustration-surface)"]', 'fill'),
        ],
        ['figure / field', figure, field],
        ['mark / field', mark, field],
        ['mark / figure', mark, figure],
        ['mark / card', mark, card],
      ];

      for (const [name, foreground, background] of pairs) {
        const ratio = contrast(foreground, background, backdrop);
        if (ratio < 3) {
          failures.push(`${theme}, ${state}, ${name}: ${ratio.toFixed(2)}`);
        }
      }
    };

    check('at rest');
    await userEvent.hover(inputs[1]!);
    check('hovered');
    await userEvent.click(inputs[1]!);
    await settle();
    await parkPointer();
    check('selected');
  }

  expect(failures).toEqual([]);
});

it('distinguishes the selected card in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const {radios} = await renderCards('40rem');
  const [selected, unselected] = [radios[1]!, radios[2]!].map((radio) =>
    getComputedStyle(cardBox(radio))
  );

  expect(selected!.borderTopColor).not.toBe(unselected!.borderTopColor);
});

it('joins the cards once shown in a container that sizes to its content', async () => {
  const {group} = await renderCards(
    '',
    (html) =>
      `<div style="display: none; grid-template-columns: min-content">${html}</div>`
  );
  const container = group.parentElement!;

  container.style.display = 'grid';
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await settle();

  expect(group.hasAttribute('data-wrapped')).toBe(false);
});

it.each([
  ['buttons', () => render('buttons'), (radio: CraftRadio) => radio],
  ['cards', () => renderCards('40rem', undefined, 'cards'), cardBox],
])(
  'draws %s as separate, fully rounded options',
  async (_appearance, renderGroup, box) => {
    const {radios} = await renderGroup();
    const rects = radios.map((radio) => box(radio).getBoundingClientRect());

    const neighbors = rects
      .slice(1)
      .map((rect, i) => [rects[i]!, rect] as const)
      .filter(([before, after]) => after.top === before.top);

    expect(neighbors.length).toBeGreaterThan(0);
    for (const [before, after] of neighbors) {
      expect(after.left).toBeGreaterThan(before.right);
    }
    for (const radio of radios) {
      const style = getComputedStyle(box(radio));
      expect(parseFloat(style.borderStartStartRadius)).toBeGreaterThan(0);
      expect(parseFloat(style.borderStartEndRadius)).toBeGreaterThan(0);
    }
  }
);

const rows = (radios: CraftRadio[]) =>
  new Set(radios.map((radio) => Math.round(radio.getBoundingClientRect().top)))
    .size;

it.each([
  ['buttons', () => render('buttons', '6rem')],
  ['button-group', () => render('button-group', '6rem')],
  ['cards', () => renderCards('12rem', undefined, 'cards')],
  ['card-group', () => renderCards('12rem')],
])('wraps %s that do not fit in one row', async (_appearance, renderGroup) => {
  const {group, radios} = await renderGroup();
  const {right} = group.getBoundingClientRect();

  expect(rows(radios)).toBeGreaterThan(1);
  for (const radio of radios) {
    expect(radio.getBoundingClientRect().right).toBeLessThanOrEqual(
      right + 0.5
    );
  }
});

it('draws a wrapped button group as separate buttons', async () => {
  const {group, radios} = await render('button-group', '6rem');

  expect(group.hasAttribute('data-wrapped')).toBe(true);
  for (const radio of radios) {
    const style = getComputedStyle(radio);
    expect(parseFloat(style.borderStartStartRadius)).toBeGreaterThan(0);
    expect(parseFloat(style.borderEndEndRadius)).toBeGreaterThan(0);
  }

  group.style.inlineSize = '40rem';
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await settle();

  expect(group.hasAttribute('data-wrapped')).toBe(false);
  expect(rows(radios)).toBe(1);
  expect(parseFloat(getComputedStyle(radios[1]!).borderStartStartRadius)).toBe(
    0
  );
});

it.each(['cards', 'card-group'])(
  'shrinks %s to fit a grid parent narrower than the row',
  async (appearance) => {
    const {group, radios} = await renderCards(
      '',
      (html) => `<div style="display: grid; inline-size: 18rem">${html}</div>`,
      appearance
    );
    const {right} = group.parentElement!.getBoundingClientRect();

    expect(group.getBoundingClientRect().right).toBeLessThanOrEqual(
      right + 0.5
    );
    expect(rows(radios)).toBeGreaterThan(1);
  }
);
