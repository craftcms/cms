import {toValue, watch, type MaybeRefOrGetter, type WatchSource} from 'vue';

const FOCUSABLE = 'a[href], button, input, select, textarea, [tabindex]';

/**
 * Keeps keyboard focus when new data redraws the same items, such as element
 * cards refetched after a save. Card parts are compiled as templates, so new
 * markup remounts them, and a focused link inside goes with it; focus moves
 * to its counterpart, unless it went somewhere else or the items changed.
 * One in a custom element, such as `craft-truncate`, gets it once the element
 * has rendered.
 *
 * @param root What holds the items.
 * @param data What the items are drawn from.
 * @param items Identifies which items are drawn, such as their IDs joined.
 */
export function useFocusThroughRedraw(
  root: MaybeRefOrGetter<HTMLElement | null | undefined>,
  data: WatchSource,
  items: () => string
): void {
  let before: {element: Element; index: number; items: string} | null = null;

  watch(
    data,
    () => {
      const container = toValue(root);
      const focused = document.activeElement;
      before =
        container && focused && container.contains(focused)
          ? {
              element: focused,
              index: [...container.querySelectorAll(FOCUSABLE)].indexOf(
                focused
              ),
              items: items(),
            }
          : null;
    },
    {flush: 'pre'}
  );

  watch(
    data,
    () => {
      const previous = before;
      before = null;
      const active = document.activeElement;
      if (
        !previous ||
        previous.items !== items() ||
        previous.element.isConnected ||
        (active && active !== document.body)
      ) {
        return;
      }
      const counterpart =
        toValue(root)?.querySelectorAll<HTMLElement>(FOCUSABLE)[previous.index];
      counterpart?.focus();
      // In a custom element that hasn't rendered its slot yet, it can't take
      // focus until it has.
      if (counterpart && document.activeElement !== counterpart) {
        requestAnimationFrame(() => {
          if (
            !document.activeElement ||
            document.activeElement === document.body
          ) {
            counterpart.focus();
          }
        });
      }
    },
    {flush: 'post'}
  );
}
