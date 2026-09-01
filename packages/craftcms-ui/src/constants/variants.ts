export const Variant = {
  Neutral: 'neutral',
  Success: 'success',
  Warning: 'warning',
  Danger: 'danger',
  Info: 'info',
} as const;

export const variants = Object.values(Variant);

export type VariantKey = keyof typeof Variant;
export type VariantValue = (typeof Variant)[keyof typeof Variant];
