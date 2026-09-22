import {t} from '@craftcms/ui';

export type CropOrientation = 'landscape' | 'portrait';

export interface ConstraintOption {
  /**
   * Stable identity, taken from the `imageEditorRatios` key.
   *
   * The selection is tracked by this rather than by `value`, which inverts with
   * the orientation — keying on the value would drop the selection the moment
   * the orientation flipped, and re-apply the ratio just switched away from.
   */
  key: string;
  value: string;
  label: string;
}

/**
 * Turns a ratio to face the given orientation: `16:9` reads `9:16` in portrait
 * and its value inverts with it.
 *
 * Only numeric ratios turn. `none` and `original` have no orientation, and a
 * square reads the same either way.
 */
export function orientConstraint(
  value: string,
  label: string,
  orientation: CropOrientation
): {value: string; label: string} {
  if (!/^\d*\.?\d+$/.test(value) || orientation === 'landscape') {
    return {value, label};
  }

  return {
    value: String(1 / parseFloat(value)),
    label: label.split(':').reverse().join(':').replace(/\s/g, ''),
  };
}

/** The constraint list for an orientation, with `Custom` last. */
export function constraintOptions(
  ratios: Record<string, string | number>,
  orientation: CropOrientation
): ConstraintOption[] {
  return [
    ...Object.entries(ratios).map(([key, ratio]) => ({
      key,
      ...orientConstraint(String(ratio), t(key), orientation),
    })),
    {key: 'custom', value: 'custom', label: t('Custom')},
  ];
}

/** The key of the unconstrained option, which is where the list starts. */
export function defaultConstraintKey(
  ratios: Record<string, string | number>
): string {
  return (
    Object.entries(ratios).find(([, ratio]) => String(ratio) === 'none')?.[0] ??
    'custom'
  );
}
