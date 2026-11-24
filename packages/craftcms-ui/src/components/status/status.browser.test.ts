import {afterEach, beforeAll, describe, expect, it} from 'vite-plus/test';

import {
  backdropOf,
  contrast,
  isPainted,
  loadTokens,
  over,
} from '../../../test/contrast';
import {neutralColors, paletteColors} from '@src/constants/colors.data';
import '../card/card.js';
import {renderedShape} from '../../../test/shape';
import './status.js';
import type CraftStatus from './status.js';

beforeAll(loadTokens);

afterEach(() => {
  document.body.innerHTML = '';
  document.body.classList.remove('use-shapes');
});

async function renderStatus(status: string): Promise<HTMLElement> {
  document.body.innerHTML = `<craft-status status="${status}" style="--size: 60px"></craft-status>`;
  const element = document.querySelector<CraftStatus>('craft-status')!;
  await element.updateComplete;

  return element.shadowRoot!.querySelector<HTMLElement>('.status')!;
}

it.each([
  ['live', 'circle'],
  ['enabled', 'circle'],
  ['disabled', 'circle'],
  ['pending', 'triangle'],
  ['expired', 'square'],
])(
  'draws a %s dot as a %s with the use-shapes preference on',
  async (status, shape) => {
    document.body.classList.add('use-shapes');

    expect(renderedShape(await renderStatus(status))).toBe(shape);
  }
);

it.each(['pending', 'expired'])(
  'draws a %s dot as a circle with the use-shapes preference off',
  async (status) => {
    expect(renderedShape(await renderStatus(status))).toBe('circle');
  }
);

const surfaces = [
  '--c-surface-default',
  '--c-surface-white',
  '--c-surface-sunken',
  '--c-surface-page',
  '--c-surface-details',
  '--c-color-accent-fill-quiet',
];

const tintedColors = paletteColors.filter(
  (color) => !(neutralColors as readonly string[]).includes(color)
);

/** A place a dot can sit, and where it should be painted. */
interface Placement {
  name: string;
  html: (dot: string) => string;
  /** The color the dot should sit on, read from the rendered page. */
  expectedBackdrop?: (container: Element) => string;
}

function cardHeader(name: string, attributes: string): Placement {
  return {
    name,
    html: (dot) =>
      `<craft-card ${attributes}><span slot="label">Block</span><span slot="actions">${dot}</span></craft-card>`,
    expectedBackdrop: (container) => {
      const card = container.querySelector('craft-card')!;
      const header = card.shadowRoot!.querySelector('.card__header')!;

      return over(getComputedStyle(header).backgroundColor, backdropOf(header));
    },
  };
}

/** The surfaces the status colors are generated for (see color-definitions.js). */
const surfacePlacements: Placement[] = [
  ...surfaces.map((surface) => ({
    name: surface,
    html: (dot: string) =>
      `<div data-surface style="background: var(${surface}); padding: 4px">${dot}</div>`,
  })),
  {
    name: 'hovered table row',
    html: (dot) =>
      `<div style="background: var(--c-surface-default)"><div data-surface style="background: var(--c-table-row-hover); padding: 4px">${dot}</div></div>`,
  },
  ...tintedColors.map((color) =>
    cardHeader(`${color} card header`, `data-color="${color}"`)
  ),
];

/** Selected card headers, where the dot takes a ring instead. */
const selectedHeaderPlacements: Placement[] = [
  cardHeader('selected card header', 'active'),
  ...tintedColors.map((color) =>
    cardHeader(`selected ${color} card header`, `data-color="${color}" active`)
  ),
];

/**
 * Renders a `status` dot in each placement and returns every one whose fill
 * and 1px ring both fall short of 3:1 against what's behind it.
 */
async function shortfalls(
  theme: string,
  status: string,
  placements: Placement[]
): Promise<string[]> {
  document.body.innerHTML = `<div data-theme="${theme}" style="background: var(--c-surface-default)">${placements
    .map(
      ({name, html}) =>
        `<div data-placement="${name}">${html(`<craft-status status="${status}"></craft-status>`)}</div>`
    )
    .join('')}</div>`;
  const elements = [
    ...document.querySelectorAll<HTMLElement>('craft-status, craft-card'),
  ] as (HTMLElement & {updateComplete: Promise<unknown>})[];
  await Promise.all(elements.map((element) => element.updateComplete));

  const failures: string[] = [];

  for (const placement of placements) {
    const container = document.querySelector(
      `[data-placement="${placement.name}"]`
    )!;
    const dot = container
      .querySelector<CraftStatus>('craft-status')!
      .shadowRoot!.querySelector('.status')!;
    const backdrop = backdropOf(dot);

    // Guards against a dot that isn't where the case says it is, which would
    // measure it against the page instead.
    if (placement.expectedBackdrop) {
      expect(backdrop, placement.name).toBe(
        placement.expectedBackdrop(container)
      );
    } else {
      const surface = container.querySelector('[data-surface]')!;
      expect(
        isPainted(getComputedStyle(surface).backgroundColor),
        `${placement.name} paints a background`
      ).toBe(true);
    }

    const {backgroundColor, borderTopColor, borderTopWidth} =
      getComputedStyle(dot);
    expect(borderTopWidth).toBe('1px');
    const ratio = Math.max(
      contrast(backgroundColor, backdrop),
      contrast(borderTopColor, backdrop)
    );

    if (ratio < 3) {
      failures.push(
        `${placement.name}: ${ratio.toFixed(2)} (fill ${backgroundColor}, ring ${borderTopColor}, on ${backdrop})`
      );
    }
  }

  return failures;
}

/**
 * A dot with no text beside it carries its state by color and, with the
 * use-shapes preference on, by shape, so it has to reach 3:1 against what's
 * behind it (WCAG 1.4.11). The colored states' fills are generated to do that
 * by themselves; disabled keeps a pale fill and relies on its darker ring.
 */
describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it.each(['live', 'enabled', 'pending', 'expired', 'disabled'])(
    'draws the %s dot at 3:1 on every surface its colors are made for',
    async (status) => {
      expect(await shortfalls(theme, status, surfacePlacements)).toEqual([]);
    }
  );

  it.each(['live', 'enabled', 'pending', 'expired', 'disabled'])(
    'rings the %s dot in a selected card header so it reaches 3:1',
    async (status) => {
      expect(await shortfalls(theme, status, selectedHeaderPlacements)).toEqual(
        []
      );
    }
  );
});
