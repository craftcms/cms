import {describe, expect, it} from 'vite-plus/test';
import {
  coverAppearance,
  COVER_BACKING,
  COVER_BACKING_HOVER,
  COVER_FILL,
  COVER_INK_OPACITY,
  type CoverTheme,
  type Luminance,
} from './cover.js';

const linear = (value: number) =>
  value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;

const toByte = (value: number) => Math.round(value * 255) / 255;

/**
 * The lowest contrast the ink gets over any sRGB pixel at all, with black
 * (dark theme) or white (light theme) laid over the pixel at `opacity`.
 * Compositing works channel by channel and is rounded to a byte, as the
 * browser draws it.
 */
function worstContrast(theme: CoverTheme, opacity: number): number {
  const surface = theme === 'dark' ? 0 : 1;
  const inkOpacity = COVER_INK_OPACITY[theme];
  const backdrop = new Float64Array(256);
  const ink = new Float64Array(256);

  for (let value = 0; value < 256; value++) {
    const behind = toByte(surface * opacity + (value / 255) * (1 - opacity));
    backdrop[value] = linear(behind);
    ink[value] = linear(
      toByte((1 - surface) * inkOpacity + behind * (1 - inkOpacity))
    );
  }

  let worst = Infinity;

  for (let r = 0; r < 256; r++) {
    for (let g = 0; g < 256; g++) {
      for (let b = 0; b < 256; b++) {
        const back =
          0.2126 * backdrop[r]! + 0.7152 * backdrop[g]! + 0.0722 * backdrop[b]!;
        const front = 0.2126 * ink[r]! + 0.7152 * ink[g]! + 0.0722 * ink[b]!;
        const ratio =
          (Math.max(front, back) + 0.05) / (Math.min(front, back) + 0.05);
        worst = Math.min(worst, ratio);
      }
    }
  }

  return worst;
}

const uniform = (p10: number, p90: number): Luminance => [
  [
    [p10, p90],
    [p10, p90],
  ],
  [
    [p10, p90],
    [p10, p90],
  ],
];

describe('cover fills', () => {
  it.each(['dark', 'light'] as const)(
    'gives %s-theme text at least 4.5:1 over any pixel',
    (theme) => {
      expect(worstContrast(theme, COVER_FILL[theme])).toBeGreaterThanOrEqual(
        4.5
      );
    }
  );

  it.each(['dark', 'light'] as const)(
    'gives %s-theme controls at least 3:1 over any pixel, resting or hovered',
    (theme) => {
      const resting = worstContrast(theme, COVER_BACKING);

      expect(resting).toBeGreaterThanOrEqual(3);
      expect(worstContrast(theme, COVER_BACKING_HOVER)).toBeGreaterThanOrEqual(
        resting
      );
    }
  );
});

describe('coverAppearance', () => {
  it.each([
    ['a light sky', uniform(0.4, 0.65)],
    ['a dark forest', uniform(0.02, 0.2)],
    ['a busy street', uniform(0.03, 0.7)],
    ['pure white', uniform(1, 1)],
    ['pure black', uniform(0, 0)],
    ['unknown', null],
    ['not sampled for luminance', []],
    ['transparent', [[null], [null]]],
  ])('uses the fixed fills over %s', (_, luminance) => {
    const labeled = coverAppearance(luminance, true);
    const unlabeled = coverAppearance(luminance, false);

    expect(labeled.header).toEqual({
      theme: labeled.header.theme,
      treatment: 'bar',
      opacity: COVER_FILL[labeled.header.theme],
    });
    expect(unlabeled.header).toEqual({
      theme: unlabeled.header.theme,
      treatment: 'backed',
      opacity: COVER_BACKING,
    });
    expect(labeled.body.opacity).toBe(COVER_FILL[labeled.body.theme]);
  });

  it('reads the header from the top row and the content from the bottom row', () => {
    const header = coverAppearance(
      [[[0.3, 0.5], null], [[0.2, 0.3]], [[0, 0.1]]],
      true
    ).header;
    const body = coverAppearance(
      [[[0.3, 0.9]], [[0.2, 0.3], null], [[0.05, 0.1]]],
      true
    ).body;

    expect(header.theme).toBe('light');
    expect(body.theme).toBe('dark');
  });

  it.each([
    [
      'dark, with deep shadows under bright highlights',
      [[[0.2, 0.95]]],
      'dark',
    ],
    [
      'dark, when one region has shadows',
      [
        [
          [0.5, 0.9],
          [0.24, 0.6],
        ],
      ],
      'dark',
    ],
    [
      'light, when every region is light',
      [
        [
          [0.5, 0.9],
          [0.25, 0.6],
        ],
      ],
      'light',
    ],
    ['light, for a pale image', [[[0.3, 0.4]]], 'light'],
  ] as const)('picks %s', (_, luminance, theme) => {
    const {header, body} = coverAppearance(luminance, true);

    expect([header.theme, body.theme]).toEqual([theme, theme]);
  });

  it.each([
    ['unknown', null],
    ['not sampled for luminance', []],
    ['transparent', [[null], [null]]],
  ])('picks white when the luminance is %s', (_, luminance) => {
    const {header, body} = coverAppearance(luminance, false);

    expect([header.theme, body.theme]).toEqual(['dark', 'dark']);
  });
});
