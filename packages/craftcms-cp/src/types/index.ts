export const Variant = {
  Default: 'default',
  Success: 'success',
  Warning: 'warning',
  Danger: 'danger',
  Info: 'info',
} as const;

export type VariantKey = (typeof Variant)[keyof typeof Variant];

export const Appearance = {
  Outline: 'outline',
  Fill: 'fill',
  OutlineFill: 'outline-fill',
  Accent: 'accent',
  Plain: 'plain',
} as const;

export type AppearanceKey = (typeof Appearance)[keyof typeof Appearance];
