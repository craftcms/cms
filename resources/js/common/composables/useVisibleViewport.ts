import {useEventListener} from '@vueuse/core';
import {shallowRef, type Ref} from 'vue';

/**
 * The part of the page's viewport that's on screen, in its coordinates: how
 * far down it starts, and how tall it is. An on-screen keyboard leaves the
 * viewport the same size on iOS, and shrinks only this. While the page is
 * pinch-zoomed it's the whole viewport, so sticky columns don't shrink to the
 * zoomed area.
 */
export function useVisibleViewport(): {
  top: Readonly<Ref<number>>;
  height: Readonly<Ref<number>>;
} {
  const top = shallowRef(0);
  const height = shallowRef(window.innerHeight);

  function update(): void {
    const visual = window.visualViewport;

    if (!visual || Math.abs(visual.scale - 1) > 0.01) {
      top.value = 0;
      height.value = window.innerHeight;

      return;
    }

    top.value = visual.offsetTop;
    height.value = visual.height;
  }

  update();
  useEventListener(window.visualViewport, ['resize', 'scroll'], update, {
    passive: true,
  });
  useEventListener(window, 'resize', update, {passive: true});

  return {top, height};
}
