export const Variant = {
  Neutral: 'neutral',
  Success: 'success',
  Warning: 'warning',
  Danger: 'danger',
  Info: 'info',
} as const;

export type VariantKey = (typeof Variant)[keyof typeof Variant];

export const Appearance = {
  Solid: 'solid',
  OutlineFill: 'outline-fill',
  Fill: 'fill',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export const ButtonAppearance = {
  Solid: 'solid',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export const ButtonVariant = {
  Accent: 'accent',
  Neutral: 'neutral',
  Danger: 'danger',
} as const;

export type AppearanceKey = (typeof Appearance)[keyof typeof Appearance];

export interface DateObject {
  date: string;
  timezone_type: string;
  timezone: string;
}

export * from './queue.js';
