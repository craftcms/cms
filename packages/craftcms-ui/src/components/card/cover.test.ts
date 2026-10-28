import {describe, expect, it} from 'vite-plus/test';
import {
  coverAppearance,
  COVER_INK_OPACITY,
  type CoverTheme,
  type Luminance,
} from './cover.js';

const channel = (luminance: number) =>
  luminance <= 0.0031308
    ? luminance * 12.92
    : 1.055 * luminance ** (1 / 2.4) - 0.055;

const linear = (value: number) =>
  value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;

const over = (top: number, opacity: number, bottom: number) =>
  top * opacity + bottom * (1 - opacity);

/**
 * The contrast of the ink against a gray region of `luminance`, with black
 * (dark theme) or white (light theme) laid over it at `opacity`.
 */
function inkContrast(
  theme: CoverTheme,
  opacity: number,
  luminance: number
): number {
  const surface = theme === 'dark' ? 0 : 1;
  const backdrop = over(surface, opacity, channel(luminance));
  const ink = over(1 - surface, COVER_INK_OPACITY[theme], backdrop);
  const [lighter, darker] = [linear(ink), linear(backdrop)].sort(
    (a, b) => b - a
  );

  return (lighter! + 0.05) / (darker! + 0.05);
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

describe('coverAppearance', () => {
  it.each([
    ['a light sky', 0.4, 0.65],
    ['a mid-gray wall', 0.15, 0.25],
    ['a dark forest', 0.02, 0.2],
    ['a busy street', 0.03, 0.7],
  ])(
    'brings the label and content up to their targets over %s',
    (_, p10, p90) => {
      const {header, body} = coverAppearance(uniform(p10, p90), true);
      const worst = (theme: CoverTheme) => (theme === 'dark' ? p90 : p10);

      expect(header.treatment).toBe('bar');
      expect(
        inkContrast(header.theme, header.opacity, worst(header.theme))
      ).toBeGreaterThanOrEqual(4.5);
      expect(
        inkContrast(body.theme, body.opacity, worst(body.theme))
      ).toBeGreaterThanOrEqual(5);
    }
  );

  it('backs the controls up to 4.5:1 where the image alone falls short', () => {
    const {header} = coverAppearance(uniform(0.15, 0.25), false);
    const worst = header.theme === 'dark' ? 0.25 : 0.15;

    expect(header.treatment).toBe('backed');
    expect(
      inkContrast(header.theme, header.opacity, worst)
    ).toBeGreaterThanOrEqual(4.5);
  });

  it('leaves the controls bare only where the image alone gives them 4.5:1', () => {
    const bare = coverAppearance(uniform(0.005, 0.11), false).header;
    const backed = coverAppearance(uniform(0.005, 0.17), false).header;

    expect(inkContrast('dark', 0, 0.11)).toBeGreaterThanOrEqual(4.5);
    expect(inkContrast('dark', 0, 0.17)).toBeLessThan(4.5);
    expect(bare).toEqual({theme: 'dark', treatment: 'bare', opacity: 0});
    expect(backed.treatment).toBe('backed');
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

  it('keeps the backing at least 65%, and the scrim 40%, so a busy image doesn’t show through', () => {
    // A region that runs from dark to light, like a pattern: its P10 alone
    // needs only about 30% white behind black ink, and 36% for the content.
    expect(coverAppearance([[[0.1, 0.8]]], false)).toEqual({
      header: {theme: 'light', treatment: 'backed', opacity: 0.65},
      body: {theme: 'light', opacity: 0.4},
    });
  });

  it.each([
    ['unknown', null],
    ['not sampled for luminance', []],
    ['transparent', [[null], [null]]],
  ])(
    'lays heavy black under white when the luminance is %s',
    (_, luminance) => {
      expect(coverAppearance(luminance, false)).toEqual({
        header: {theme: 'dark', treatment: 'backed', opacity: 0.7},
        body: {theme: 'dark', opacity: 0.8},
      });
      expect(coverAppearance(luminance, true).header).toEqual({
        theme: 'dark',
        treatment: 'bar',
        opacity: 0.75,
      });
    }
  );
});
