/**
 * How a cover card lays its header and content over its image, worked out from
 * the image's luminance.
 *
 * Text and controls are white or black, a little transparent, and sit on
 * white or black laid over the image: a header bar when the header has a
 * label, a backing behind its controls when they wouldn't hold up on the image
 * alone, and a scrim behind the content. Each is as light as it can be while
 * still meeting its contrast target.
 *
 * The luminance is sampled per region of the image, as its 10th and 90th
 * percentiles, so up to a tenth of a region can be darker or lighter than
 * what's known. The targets leave room for that: they were tuned against the
 * actual pixels behind the text and controls of a range of photos, with the
 * text hidden, so that even the brightest or darkest of them meets WCAG.
 */

/** A region's [P10, P90] relative luminance, or `null` if it's transparent. */
export type LuminanceRange = readonly [number, number] | null;

/** Rows of regions, top to bottom, each listed left to right. */
export type Luminance = readonly (readonly LuminanceRange[])[];

/** `dark` is white on black; `light` is black on white. */
export type CoverTheme = 'light' | 'dark';

/**
 * - `bar`: the header has a label, so it gets a bar of its own.
 * - `backed`: no label, and the controls wouldn't hold up on the image, so
 *   they get a backing.
 * - `bare`: no label, and the controls hold up on the image.
 */
export type CoverHeaderTreatment = 'bar' | 'backed' | 'bare';

export interface CoverAppearance {
  header: {
    theme: CoverTheme;
    treatment: CoverHeaderTreatment;
    /** Opacity of the bar or the controls' backing; 0 when bare. */
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

interface Target {
  /** Contrast to aim for against the region's P10/P90. */
  contrast: number;
  /** Least opacity, for detail too small to show in the luminance. */
  min: number;
  /** Opacity when the image's luminance isn't known. */
  unknown: number;
}

/**
 * Text needs 4.5:1. The bar's blur evens out bright and dark spots behind it,
 * so 4.5:1 against the P10/P90 holds; the content's scrim isn't blurred, so it
 * aims for 7:1, and is never fainter than 55%, as a thin bright line (a road,
 * a sunlit edge) can be missing from the luminance entirely.
 */
const BAR: Target = {contrast: 4.5, min: 0.15, unknown: 0.75};
const SCRIM: Target = {contrast: 7, min: 0.55, unknown: 0.8};

/**
 * Controls need 3:1 against what's next to them. Their backing aims for 4.5:1
 * against the P10/P90 for the same reason, and they're only left bare when
 * the image itself reaches 4.5:1.
 */
const BACKING: Target = {contrast: 4.5, min: 0.5, unknown: 0.6};

const toLinear = (channel: number) =>
  channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;

const toSrgb = (luminance: number) =>
  luminance <= 0.0031308
    ? luminance * 12.92
    : 1.055 * luminance ** (1 / 2.4) - 0.055;

const contrastRatio = (a: number, b: number) =>
  (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);

/**
 * The least luminance the semi-transparent ink can have: white over black, or
 * black over white.
 */
const INK_LUMINANCE: Record<CoverTheme, number> = {
  dark: toLinear(COVER_INK_OPACITY.dark),
  light: toLinear(1 - COVER_INK_OPACITY.light),
};

interface Measurement {
  theme: CoverTheme;
  /** The ink's contrast against the region, with nothing laid over it. */
  contrast: number;
  /** The region's lightest P90 (dark theme) or darkest P10 (light theme). */
  worst: number;
}

/**
 * Picks white or black ink for some rows of the image, as
 * `ImageColors::textColor()` does: white against the lightest P90, black
 * against the darkest P10, whichever contrasts more.
 */
function measure(rows: Luminance): Measurement | null {
  const ranges = rows
    .flat()
    .filter((range): range is readonly [number, number] => range !== null);

  if (!ranges.length) {
    return null;
  }

  const darkest = Math.min(...ranges.map(([p10]) => p10));
  const lightest = Math.max(...ranges.map(([, p90]) => p90));
  const white = contrastRatio(INK_LUMINANCE.dark, lightest);
  const black = contrastRatio(INK_LUMINANCE.light, darkest);

  return white >= black
    ? {theme: 'dark', contrast: white, worst: lightest}
    : {theme: 'light', contrast: black, worst: darkest};
}

/**
 * The least opacity of black (dark theme) or white (light theme) over a region
 * whose worst luminance is `worst` that brings the ink up to `contrast`.
 * Treats the region as gray, and composites in sRGB, as the browser does.
 */
function opacityFor(
  theme: CoverTheme,
  worst: number,
  contrast: number
): number {
  const channel = toSrgb(worst);

  if (theme === 'dark') {
    const most = Math.max(0, (INK_LUMINANCE.dark + 0.05) / contrast - 0.05);
    return channel > 0 ? Math.max(0, 1 - toSrgb(most) / channel) : 0;
  }

  const least = Math.min(1, contrast * (INK_LUMINANCE.light + 0.05) - 0.05);
  return channel < 1
    ? Math.max(0, (toSrgb(least) - channel) / (1 - channel))
    : 0;
}

function opacity(measurement: Measurement | null, target: Target): number {
  if (!measurement) {
    return target.unknown;
  }

  const needed = opacityFor(
    measurement.theme,
    measurement.worst,
    target.contrast
  );

  return Math.max(target.min, Math.ceil(Math.min(needed, 1) * 100) / 100);
}

/**
 * Works out a cover card's appearance from its image's luminance. Without
 * luminance (the image hasn't been sampled), both parts are dark, at the
 * `unknown` opacities.
 *
 * @param luminance - The image's luminance grid, or `null` if it isn't known
 * @param labeled - Whether the header has a label
 */
export function coverAppearance(
  luminance: Luminance | null | undefined,
  labeled: boolean
): CoverAppearance {
  const rows = Array.isArray(luminance) ? luminance : [];
  const top = rows.length ? measure(rows.slice(0, 1)) : null;
  const bottom = rows.length ? measure(rows.slice(-1)) : null;

  let treatment: CoverHeaderTreatment;
  let headerOpacity: number;

  if (labeled) {
    treatment = 'bar';
    headerOpacity = opacity(top, BAR);
  } else if (!top || top.contrast < BACKING.contrast) {
    treatment = 'backed';
    headerOpacity = opacity(top, BACKING);
  } else {
    treatment = 'bare';
    headerOpacity = 0;
  }

  return {
    header: {theme: top?.theme ?? 'dark', treatment, opacity: headerOpacity},
    body: {theme: bottom?.theme ?? 'dark', opacity: opacity(bottom, SCRIM)},
  };
}
