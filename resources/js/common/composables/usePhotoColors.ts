import {computed, toValue, type ComputedRef, type MaybeRefOrGetter} from 'vue';

/**
 * A user's default photo colors, as `--photo-color-start` and
 * `--photo-color-end` custom properties, for tinting what they wrote with the
 * gradient their default photo uses.
 */
export function usePhotoColors(
  colors: MaybeRefOrGetter<string[] | null | undefined>
): ComputedRef<Record<string, string>> {
  return computed((): Record<string, string> => {
    const pair = toValue(colors);

    return pair
      ? {
          '--photo-color-start': `var(--color-${pair[0]}-500)`,
          '--photo-color-end': `var(--color-${pair[1]}-500)`,
        }
      : {};
  });
}
