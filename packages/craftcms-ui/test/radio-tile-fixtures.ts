const FILL = '#F3F7FD';
const STROKE = '#828FA4';

const svg = (width: number, shapes: string) =>
  `data:image/svg+xml,${encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="60" viewBox="0 0 ${width} 60">` +
      `<style>@media (prefers-color-scheme: dark) { [fill="${FILL}"] { fill: #333D51; } [stroke="${STROKE}"] { stroke: #7C899F; } }</style>` +
      shapes +
      '</svg>'
  )}`;

const chip = (x: number, y: number, width: number, height: number) =>
  `<rect x="${x + 0.5}" y="${y + 0.5}" width="${width - 1}" height="${height - 1}" rx="${height > 10 ? 5 : 2}" fill="${FILL}" stroke="${STROKE}"/>`;

export interface RadioTileOption {
  value: string;
  label: string;
  src: string;
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
    src: svg(
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
    src: svg(80, [0, 21, 42].map((y) => chip(0, y, 80, 18)).join('')),
  },
  {
    value: 'cards-grid',
    label: 'Card grid',
    width: 80,
    src: svg(
      80,
      [0, 21, 42].map((y) => chip(0, y, 38, 18) + chip(42, y, 38, 18)).join('')
    ),
  },
  {
    value: 'blocks',
    label: 'Blocks',
    width: 80,
    src: svg(80, chip(0, 0, 80, 28) + chip(0, 32, 80, 28)),
  },
  {
    value: 'index',
    label: 'Index',
    width: 80,
    src: svg(
      80,
      chip(0, 0, 80, 60) +
        `<path d="M0.5 11.5H79.5" stroke="${STROKE}"/>` +
        chip(15, 19, 50, 14) +
        chip(15, 37, 50, 14)
    ),
  },
];
