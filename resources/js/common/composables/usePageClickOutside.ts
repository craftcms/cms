import {toValue, type MaybeRefOrGetter} from 'vue';
import {useEventListener} from '@vueuse/core';

/**
 * Calls `handler` on a click elsewhere on the page than `inside`, while
 * `active` holds. Clicks in menus, modals and slideouts opened over the page
 * don't count: those are appended to `<body>`, outside the element the page is
 * mounted in.
 *
 * Captured, so a control that opens what this closes does so afterwards.
 */
export function usePageClickOutside(
  inside: MaybeRefOrGetter<(Element | null | undefined)[]>,
  active: MaybeRefOrGetter<boolean>,
  handler: () => void
): void {
  useEventListener(
    document,
    'click',
    (event: MouseEvent) => {
      if (!toValue(active)) {
        return;
      }

      const elements = toValue(inside).filter(
        (element): element is Element => !!element
      );
      const path = event.composedPath();

      let [pageRoot] = elements;

      if (!pageRoot || elements.some((element) => path.includes(element))) {
        return;
      }

      while (
        pageRoot.parentElement &&
        pageRoot.parentElement !== document.body
      ) {
        pageRoot = pageRoot.parentElement;
      }

      if (path.includes(pageRoot)) {
        handler();
      }
    },
    {capture: true}
  );
}
