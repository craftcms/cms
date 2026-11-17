/**
 * How a cover card lays its header and content over its image.
 *
 * Text and controls are white or black, a little transparent, and sit on
 * black or white laid over the image: a header bar when the header has text,
 * a backing behind its controls when it doesn't, and a scrim behind the
 * content. Each is a fixed opacity that gives the ink its contrast over any
 * pixel at all, pure white under white ink or pure black under black ink, so
 * the contrast doesn't depend on the image, how it's cropped, or whether it
 * has loaded.
 *
 * The image's luminance only picks white or black, whichever suits it: from
 * its top row for the header, and its bottom row for the content. White on
 * black is used unless that part of the image is light throughout, as a black
 * scrim reads as a natural shadow, but turns a washed-out image gray.
 */

/** A region's [P10, P90] relative luminance, or `null` if it's transparent. */
export type LuminanceRange = readonly [number, number] | null;

/** Rows of regions, top to bottom, each listed left to right. */
export type Luminance = readonly (readonly LuminanceRange[])[];

/** `dark` is white on black; `light` is black on white. */
export type CoverTheme = 'light' | 'dark';

/**
 * - `bar`: the header has text, so it gets a bar of its own.
 * - `backed`: the header has only controls, so they get a backing.
 */
export type CoverHeaderTreatment = 'bar' | 'backed';

export interface CoverAppearance {
  header: {
    theme: CoverTheme;
    treatment: CoverHeaderTreatment;
    /** Opacity of the bar or the controls' backing. */
    opacity: number;
  };
  body: {
    theme: CoverTheme;
    /** Opacity of the scrim behind the content, at its full strength. */
    opacity: number;
  };
}

/** The opacity of the white and black text and controls. */
export const COVER_INK_OPACITY: Record<CoverTheme, number> = {
  dark: 0.9,
  light: 0.85,
};

/**
 * The opacity of the black (dark theme) or white (light theme) behind text,
 * which brings the ink to at least 4.5:1 over any pixel.
 */
export const COVER_FILL: Record<CoverTheme, number> = {
  dark: 0.6,
  light: 0.52,
};

/**
 * The opacity of the backing behind header controls, which is more than they
 * need for 3:1, so a busy image doesn't show through it.
 */
export const COVER_BACKING = 0.65;

/**
 * The opacity of a hovered or pressed control's backing: more of the same
 * black or white, so the control only gains contrast.
 */
export const COVER_BACKING_HOVER = 0.8;

/**
 * The least relative luminance the darkest part (P10) of every region must
 * have for black ink on white to be used instead.
 */
export const COVER_LIGHT_THEME_MIN_LUMINANCE = 0.25;

/**
 * Picks white or black ink for some rows of the image: black when even their
 * darkest region's P10 is at least {@link COVER_LIGHT_THEME_MIN_LUMINANCE},
 * and white otherwise.
 */
function theme(rows: Luminance): CoverTheme | null {
  const ranges = rows
    .flat()
    .filter((range): range is readonly [number, number] => range !== null);

  if (!ranges.length) {
    return null;
  }

  const darkest = Math.min(...ranges.map(([p10]) => p10));

  return darkest >= COVER_LIGHT_THEME_MIN_LUMINANCE ? 'light' : 'dark';
}

/**
 * Works out a cover card's appearance from its image's luminance. Without
 * luminance (the image hasn't been sampled), both parts are dark.
 *
 * @param luminance - The image's luminance grid, or `null` if it isn't known
 * @param hasText - Whether the header has text, such as a label or badge
 */
export function coverAppearance(
  luminance: Luminance | null | undefined,
  hasText: boolean
): CoverAppearance {
  const rows = Array.isArray(luminance) ? luminance : [];
  const top = (rows.length && theme(rows.slice(0, 1))) || 'dark';
  const bottom = (rows.length && theme(rows.slice(-1))) || 'dark';

  return {
    header: hasText
      ? {theme: top, treatment: 'bar', opacity: COVER_FILL[top]}
      : {theme: top, treatment: 'backed', opacity: COVER_BACKING},
    body: {theme: bottom, opacity: COVER_FILL[bottom]},
  };
}
