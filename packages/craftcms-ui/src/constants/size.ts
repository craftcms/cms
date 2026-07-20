const Size = {
  small: 'small',
  medium: 'medium',
  large: 'large',
} as const;

export const sizes = Object.values(Size);

export type SizeKey = keyof typeof Size;
export type SizeValue = (typeof Size)[keyof typeof Size];
