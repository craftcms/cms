import {useEventListener, useResizeObserver} from '@vueuse/core';
import type {Ref} from 'vue';

/** How close to the bottom, in pixels, still counts as being at it. */
const PIN_DISTANCE = 24;

/**
 * Keeps a scrolling element at the bottom as its content grows, such as a
 * conversation or a timeline: until the user scrolls up, and again once they
 * scroll back down to it.
 *
 * @param scroller The element that scrolls.
 * @param content What grows, observed so content that finishes rendering after
 *   Vue's update is still followed: an element, or a getter for several.
 */
export function usePinnedScroll(
  scroller: Readonly<Ref<HTMLElement | null>>,
  content: Parameters<typeof useResizeObserver>[0],
  {pinned = true}: {pinned?: boolean} = {}
) {
  let isPinned = pinned;

  /** Where it was last scrolled to, by the user or by pinning, to tell scrolling up from down. */
  let lastScrollTop = 0;

  /** Whether it was pinned when an input's text started to change. */
  let wasPinnedBeforeInput = false;

  useEventListener(
    scroller,
    'scroll',
    () => {
      const element = scroller.value;

      if (!element) {
        return;
      }

      const isAtBottom =
        element.scrollHeight - element.scrollTop - element.clientHeight <=
        PIN_DISTANCE;

      // Only scrolling up unpins it: as content grows, the browser also scrolls
      // down a little to keep what's in view in place, and the scroll pinning
      // sets off can arrive after more has rendered below it.
      isPinned =
        isAtBottom || (isPinned && element.scrollTop >= lastScrollTop - 1);
      lastScrollTop = element.scrollTop;
    },
    {passive: true}
  );

  /** Keeps the newest content in view, unless the user has scrolled up. */
  function stick(): void {
    if (isPinned && scroller.value) {
      scroller.value.scrollTop = scroller.value.scrollHeight;
      lastScrollTop = scroller.value.scrollTop;
    }
  }

  useResizeObserver(content, stick);

  return {
    stick,

    /** Pins it again, wherever the user had scrolled to. */
    pin(): void {
      isPinned = true;
    },

    /** For `beforeinput` on an input outside the scroller. */
    onBeforeInput(): void {
      wasPinnedBeforeInput = isPinned;
    },

    /**
     * For `input` on an input outside the scroller. Firefox scrolls the
     * scroller when the input's text changes, although the input isn't in it.
     * Pinning it again before the next paint hides that, and keeps the scroll
     * from counting as the user's.
     */
    onInput(): void {
      if (!wasPinnedBeforeInput) {
        return;
      }

      requestAnimationFrame(() => {
        isPinned = true;
        stick();
      });
    },
  };
}
