import {computed, watch, type Ref} from 'vue';
import {
  unrefElement,
  useElementBounding,
  useWindowSize,
  type MaybeComputedElementRef,
} from '@vueuse/core';

/**
 * How many pixels of an element are inside the viewport, kept up to date as
 * the page scrolls and resizes.
 *
 * `PageScreen` sizes its sticky details pane from what's still showing of the
 * top bar, which scrolls away with the page: subtracting its full height would
 * leave a gap under the pane once the bar is gone, and not subtracting it would
 * push the pane's bottom out of view before it is.
 */
export function useVisibleHeight(
  target: MaybeComputedElementRef
): Readonly<Ref<number>> {
  const {top, bottom, update} = useElementBounding(target);
  const {height: viewportHeight} = useWindowSize();

  // Measured as soon as the element changes, rather than whenever the resize
  // observer next reports on it.
  watch(() => unrefElement(target), update, {flush: 'post'});

  return computed(() =>
    Math.max(
      0,
      Math.min(bottom.value, viewportHeight.value) - Math.max(top.value, 0)
    )
  );
}
