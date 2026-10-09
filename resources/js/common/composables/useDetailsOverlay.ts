import {computed, type Ref} from 'vue';

/**
 * Reports whether a shell's layout has lifted the details column out of the
 * flow to overlay the content.
 *
 * The threshold lives in the shell's own container query, which publishes
 * `--cp-details-overlay` on the element passed here; a query condition can't
 * read a custom property, so this reads the result back rather than restating
 * the width. `width` isn't the threshold, only what prompts a re-read.
 */
export function useDetailsOverlay(
  element: () => HTMLElement | null | undefined,
  width: Readonly<Ref<number>>
): Readonly<Ref<boolean>> {
  return computed(() => {
    void width.value;

    const target = element();

    return (
      !!target &&
      getComputedStyle(target)
        .getPropertyValue('--cp-details-overlay')
        .trim() === '1'
    );
  });
}
