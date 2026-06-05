export const Appearance = {
  Accent: 'accent',
  OutlineFill: 'outline-fill',
  Fill: 'fill',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export const appearances = Object.values(Appearance);

export type AppearanceKey = (typeof Appearance)[keyof typeof Appearance];
export type AppearanceValue = typeof appearances;
