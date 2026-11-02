const FILL = '#F3F7FD';
const STROKE = '#828FA4';

/** Inline markup, so the CP's `--c-illustration-*` colors can restyle it. */
const svg = (width: number, shapes: string) =>
  `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="60" viewBox="0 0 ${width} 60">${shapes}</svg>`;

const chip = (x: number, y: number, width: number, height: number) =>
  `<rect x="${x + 0.5}" y="${y + 0.5}" width="${width - 1}" height="${height - 1}" rx="${height > 10 ? 5 : 2}" fill="${FILL}" stroke="${STROKE}"/>`;

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
        `<path d="M0.5 11.5H79.5" stroke="${STROKE}"/>` +
        chip(15, 19, 50, 14) +
        chip(15, 37, 50, 14)
    ),
  },
];

const SKY = '#E9EEF3';
const MOUNTAIN = '#A6B2C2';
const INK = '#5A6572';
const HALO = '#FFFFFF';

/**
 * A photo inside a dashed crop frame with corner marks, like the image
 * transform Mode illustrations: an outlined sky, mountains, and marks that
 * cross both.
 */
export const CROP_MODE: RadioTileOption = {
  value: 'crop',
  label: 'Crop',
  width: 80,
  svg: svg(
    80,
    `<rect x="0.5" y="4.5" width="79" height="51" fill="${SKY}" stroke="${STROKE}"/>` +
      `<path d="M1 46 22 22 36 37 48 26 79 51V55H1Z" fill="${MOUNTAIN}"/>` +
      `<path d="M14.5 2.5H65.5V57.5H14.5Z" fill="none" stroke="${INK}" stroke-dasharray="1"/>` +
      `<path d="M12 0H20V4H16V8H12Z" fill="${INK}" stroke="${HALO}" stroke-width="2" paint-order="stroke"/>` +
      `<path d="M68 60H60V56H64V52H68Z" fill="${INK}" stroke="${HALO}" stroke-width="2" paint-order="stroke"/>`
  ),
};
