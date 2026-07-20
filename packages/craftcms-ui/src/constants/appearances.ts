export const Appearance = {
  Solid: 'solid',
  OutlineFill: 'outline-fill',
  Fill: 'fill',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export const appearances = Object.values(Appearance);

export type AppearanceKey = keyof typeof Appearance;
export type AppearanceValue = (typeof Appearance)[keyof typeof Appearance];
