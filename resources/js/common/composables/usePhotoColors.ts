import {usePage} from '@inertiajs/vue3';
import {computed, toValue, type ComputedRef, type MaybeRefOrGetter} from 'vue';
import type {CraftData} from '@/common/composables/useCraftData';

/**
 * A user's default photo colors, as `--photo-color-start` and
 * `--photo-color-end` custom properties, for tinting what they wrote with the
 * gradient their default photo uses. Without colors, it's the current user's.
 */
export function usePhotoColors(
  colors?: MaybeRefOrGetter<string[] | null | undefined>
): ComputedRef<Record<string, string>> {
  const page = usePage<{craft?: CraftData}>();

  return computed((): Record<string, string> => {
    const pair =
      colors === undefined
        ? page.props?.craft?.currentUser?.photoColors
        : toValue(colors);

    return pair
      ? {
          '--photo-color-start': `var(--color-${pair[0]}-500)`,
          '--photo-color-end': `var(--color-${pair[1]}-500)`,
        }
      : {};
  });
}
