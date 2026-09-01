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
