export const Variant = {
  Default: 'default',
  Success: 'success',
  Warning: 'warning',
  Danger: 'danger',
  Info: 'info',
} as const;

export type VariantKey = (typeof Variant)[keyof typeof Variant];

export const Appearance = {
  Accent: 'accent',
  OutlineFill: 'outline-fill',
  Fill: 'fill',
  Outline: 'outline',
  Plain: 'plain',
} as const;

export type AppearanceKey = (typeof Appearance)[keyof typeof Appearance];

export interface DateObject {
  date: string;
  timezone_type: string;
  timezone: string;
}

export * from './queue.js';
