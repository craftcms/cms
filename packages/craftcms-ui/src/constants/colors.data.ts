/**
 * Canonical color data — the single source of truth for colors in the design
 * system.
 *
 * Consumed by:
 * - `constants/colors.ts` (runtime `Color` map + types), and
 * - `scripts/generate-colors.js` (generates `styles/shared/colorable.css`),
 *   which transpiles this file with esbuild since it can't import TS directly.
 *
 * Keep it dependency-free so it stays trivially transpilable.
 */

/** The raw color palette (each has a full `--color-<name>-*` scale). */
export const paletteColors = [
  'red',
  'orange',
  'amber',
  'yellow',
  'lime',
  'green',
  'emerald',
  'teal',
  'cyan',
  'sky',
  'blue',
  'indigo',
  'violet',
  'purple',
  'fuchsia',
  'pink',
  'rose',
  'white',
  'gray',
  'black',
  'slate',
] as const;

/**
 * Palette colors that are neutral rather than tinted. A component in one of
 * these colors keeps the usual UI colors for what's in it (links, controls,
 * text) instead of taking on its own color, as tinted colors do. Black's
 * contents are themed dark instead.
 */
export const neutralColors = ['white', 'gray', 'slate', 'black'] as const;

/** Semantic meaning → palette color. */
export const semanticColors = {
  neutral: 'slate',
  accent: 'blue',
  info: 'sky',
  success: 'emerald',
  warning: 'yellow',
  danger: 'red',
} as const;

/** Status name → palette color. */
export const statusColors = {
  pending: 'orange',
  off: 'red',
  suspended: 'red',
  expired: 'red',
  disabled: 'gray',
  inactive: 'gray',
  on: 'emerald',
} as const;
