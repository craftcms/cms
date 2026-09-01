import {paletteColors, semanticColors, statusColors} from './colors.data';

type PaletteColor = (typeof paletteColors)[number];

/**
 * Named color map (label → palette color), combining the raw palette, semantic
 * meanings, and status names from the shared {@link ./colors.data} source.
 */
type NamedColors = {
  readonly [K in PaletteColor as Capitalize<K>]: K;
} & {
  readonly [K in keyof typeof semanticColors as Capitalize<K>]: (typeof semanticColors)[K];
} & {
  readonly [K in keyof typeof statusColors as Capitalize<K>]: (typeof statusColors)[K];
};

const capitalize = (value: string): string =>
  value.charAt(0).toUpperCase() + value.slice(1);

export const Color = {
  ...Object.fromEntries(
    paletteColors.map((color) => [capitalize(color), color])
  ),
  ...Object.fromEntries(
    Object.entries(semanticColors).map(([name, color]) => [
      capitalize(name),
      color,
    ])
  ),
  ...Object.fromEntries(
    Object.entries(statusColors).map(([name, color]) => [
      capitalize(name),
      color,
    ])
  ),
} as NamedColors;

/** All valid color values (the palette). */
export const colors: PaletteColor[] = [...paletteColors];

export type ColorKey = keyof NamedColors;
export type ColorValue = PaletteColor;
