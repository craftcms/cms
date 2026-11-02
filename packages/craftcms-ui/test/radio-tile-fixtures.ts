/** The roles in the illustration palette, one per `--c-illustration-*` token. */
export type IllustrationRole =
  | 'surface'
  | 'paper'
  | 'subtle'
  | 'field'
  | 'figure'
  | 'line'
  | 'mark';

/**
 * A `style` that paints with the `--c-illustration-*` tokens, as the CP's
 * illustrations do: `fill` and `stroke` each name a role, such as `figure`.
 */
const paint = (fill?: IllustrationRole, stroke?: IllustrationRole) =>
  `style="${[
    fill && `fill: var(--c-illustration-${fill})`,
    stroke && `stroke: var(--c-illustration-${stroke})`,
  ]
    .filter(Boolean)
    .join('; ')}"`;

/** Inline markup, so the CP's `--c-illustration-*` colors can restyle it. */
const svg = (width: number, shapes: string) =>
  `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="60" viewBox="0 0 ${width} 60">${shapes}</svg>`;

const chip = (x: number, y: number, width: number, height: number) =>
  `<rect x="${x + 0.5}" y="${y + 0.5}" width="${width - 1}" height="${height - 1}" rx="${height > 10 ? 5 : 2}" ${paint('surface', 'line')}/>`;

export interface RadioTileOption {
  value: string;
  label: string;
  svg: string;
  width: number;
}

/**
 * Layout illustrations like the ones Craft's View Mode setting uses, one per
 * option. The list is narrower than the rest, as the real one is.
 */
export const VIEW_MODES: RadioTileOption[] = [
  {
    value: 'list',
    label: 'List',
    width: 48,
    svg: svg(
      48,
      [0, 13, 26, 39, 52]
        .map((y, i) => chip(0, y, [33, 38, 48, 38, 43][i]!, 8))
        .join('')
    ),
  },
  {
    value: 'cards',
    label: 'Cards',
    width: 80,
    svg: svg(80, [0, 21, 42].map((y) => chip(0, y, 80, 18)).join('')),
  },
  {
    value: 'cards-grid',
    label: 'Card grid',
    width: 80,
    svg: svg(
      80,
      [0, 21, 42].map((y) => chip(0, y, 38, 18) + chip(42, y, 38, 18)).join('')
    ),
  },
  {
    value: 'blocks',
    label: 'Blocks',
    width: 80,
    svg: svg(80, chip(0, 0, 80, 28) + chip(0, 32, 80, 28)),
  },
  {
    value: 'index',
    label: 'Index',
    width: 80,
    svg: svg(
      80,
      chip(0, 0, 80, 60) +
        `<path d="M0.5 11.5H79.5" ${paint(undefined, 'line')}/>` +
        chip(15, 19, 50, 14) +
        chip(15, 37, 50, 14)
    ),
  },
];

/**
 * A sample illustration that uses every role in the illustration palette, in
 * every pairing it promises 3:1 for: outlined `surface` and `paper` shapes,
 * `subtle` bars, mountains (`figure`) on an outlined `field`, and `mark`s that
 * cross them.
 */
export const PALETTE_SAMPLE: RadioTileOption = {
  value: 'palette',
  label: 'Palette',
  width: 80,
  svg: svg(
    80,
    `<rect x="0.5" y="0.5" width="35" height="59" rx="4" ${paint('surface', 'line')}/>` +
      `<rect x="5" y="6" width="22" height="4" rx="2" ${paint('subtle')}/>` +
      `<rect x="5" y="14" width="16" height="4" rx="2" ${paint('subtle')}/>` +
      `<rect x="5.5" y="24.5" width="25" height="29" rx="2" ${paint('paper', 'line')}/>` +
      `<rect x="42.5" y="12.5" width="37" height="35" ${paint('field', 'line')}/>` +
      `<path d="M43 40 52 30 58 36 64 31 79 44V47H43Z" ${paint('figure')}/>` +
      `<circle cx="72" cy="20" r="3" ${paint('figure')}/>` +
      `<rect x="43" y="47" width="36" height="5" ${paint('mark')}/>` +
      `<path d="M47.5 8.5H74.5V55.5H47.5Z" fill="none" ${paint(undefined, 'mark')} stroke-dasharray="1"/>` +
      `<path d="M46 7H51V9H48V12H46Z" ${paint('mark')}/>` +
      `<path d="M76 57H71V55H74V52H76Z" ${paint('mark')}/>`
  ),
};
