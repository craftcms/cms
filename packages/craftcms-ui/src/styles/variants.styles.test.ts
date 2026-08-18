import {describe, expect, it} from 'vite-plus/test';
import {paletteColors, semanticColors} from '@src/constants/colors.data.js';
import variantsStyles, {paletteStyles} from './variants.styles.js';

const css = variantsStyles.cssText;

const tokens = [
  'fill-loud',
  'fill-normal',
  'fill-quiet',
  'border-loud',
  'border-normal',
  'border-quiet',
  'on-loud',
  'on-normal',
  'on-quiet',
];

/**
 * These assert the shape of the sheet rather than resolved colors: happy-dom
 * doesn't resolve custom properties through `getComputedStyle`, so the values
 * themselves are checked in the browser (see the stories).
 */
describe('variant styles', () => {
  it.each(Object.keys(semanticColors))('maps every %s token', (variant) => {
    for (const token of tokens) {
      expect(css).toContain(
        `--c-color-${token}: var(--c-color-${variant}-${token});`
      );
    }
  });

  /**
   * Both selectors, for every variant: the host wears it, and so does any
   * element inside the shadow root that asks for it by attribute.
   */
  it.each(Object.keys(semanticColors))(
    'scopes %s to host and region',
    (variant) => {
      expect(css).toContain(`:host([variant~='${variant}'])`);
      expect(css).toContain(`[data-variant~='${variant}']`);
    }
  );

  /**
   * `~=` throughout, so a multi-valued attribute (`variant="neutral outline"`)
   * still matches. `neutral` used to be the one exception.
   */
  it('matches variants among several', () => {
    expect(css).not.toMatch(/\[variant='/);
    expect(css).not.toMatch(/\[data-variant='/);
  });
});

/**
 * The shadow-DOM twin of `shared/colorable.css`. Both come out of
 * `scripts/generate-colors.js`, so this mostly guards the shape the generator
 * emits — the two can't drift in content.
 */
describe('palette styles', () => {
  const palette = paletteStyles.cssText;

  it('covers every palette and semantic color', () => {
    for (const color of [...paletteColors, ...Object.keys(semanticColors)]) {
      expect(palette).toContain(`[data-color='${color}']`);
    }
  });

  it('reaches a host and any element inside the shadow root', () => {
    expect(palette).toContain(":host([data-color='red'])");
    expect(palette).toContain("[data-color='red']");
  });

  /** Variants stay the constrained vocabulary; the palette is the open one. */
  it('leaves the variant attribute to the variant styles', () => {
    expect(palette).not.toContain('variant');
    expect(css).not.toContain('data-color');
  });
});
