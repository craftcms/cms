import {toValue, watch, type MaybeRefOrGetter, type WatchSource} from 'vue';

const FOCUSABLE = 'a[href], button, input, select, textarea, [tabindex]';

/** How long a scoped item’s content is waited for, in ms. */
const CONTENT_WAIT = 5000;

export interface FocusThroughRedrawOptions {
  /**
   * Matches the items, each with a `data-id`, such as dashboard widgets.
   * Focus then moves to its counterpart in the same item, which is waited for
   * if the item adds its content later, as HTML widgets do. Without it, focus
   * moves to the counterpart at the same position in `root`.
   */
  itemSelector?: string;
}

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
  items: () => string,
  {itemSelector}: FocusThroughRedrawOptions = {}
): void {
  /**
   * The items the DOM shows. By the time `data` changes, `items()` already
   * describes the new ones.
   */
  let rendered = items();
  let before: {
    element: Element;
    items: string;
    item: string | null;
    index: number;
  } | null = null;

  /** Where focus would go back to: the focused item, or all of `root`. */
  function scopeOf(id: string | null): HTMLElement | null {
    const container = toValue(root) ?? null;

    return id !== null && itemSelector
      ? (container?.querySelector<HTMLElement>(
          `${itemSelector}[data-id="${CSS.escape(id)}"]`
        ) ?? null)
      : container;
  }

  watch(
    data,
    () => {
      const container = toValue(root);
      const focused = document.activeElement;
      if (!container || !focused || !container.contains(focused)) {
        before = null;
        return;
      }

      const item = itemSelector
        ? (focused.closest<HTMLElement>(itemSelector)?.dataset.id ?? null)
        : null;
      const scope = scopeOf(item);
      before = scope
        ? {
            element: focused,
            items: rendered,
            item,
            index: [...scope.querySelectorAll(FOCUSABLE)].indexOf(focused),
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
      const current = items();
      rendered = current;
      if (
        !previous ||
        previous.index < 0 ||
        previous.items !== current ||
        previous.element.isConnected ||
        !focusIsFree()
      ) {
        return;
      }

      const scope = scopeOf(previous.item);
      if (!scope) {
        return;
      }

      const counterpart = () =>
        scope.querySelectorAll<HTMLElement>(FOCUSABLE)[previous.index];
      const found = counterpart();
      if (found) {
        focusCounterpart(found);
      } else if (previous.item !== null) {
        waitForContent(scope, counterpart);
      }
    },
    {flush: 'post'}
  );
}

function focusIsFree(): boolean {
  return !document.activeElement || document.activeElement === document.body;
}

function focusCounterpart(counterpart: HTMLElement): void {
  counterpart.focus();
  // In a custom element that hasn't rendered its slot yet, it can't take
  // focus until it has.
  if (document.activeElement !== counterpart) {
    requestAnimationFrame(() => {
      if (focusIsFree()) {
        counterpart.focus();
      }
    });
  }
}

/** Focuses the counterpart once an item that adds its content later has. */
function waitForContent(
  scope: HTMLElement,
  counterpart: () => HTMLElement | undefined
): void {
  const observer = new MutationObserver(() => {
    if (!scope.isConnected || !focusIsFree()) {
      observer.disconnect();
      return;
    }
    const found = counterpart();
    if (found) {
      observer.disconnect();
      focusCounterpart(found);
    }
  });
  observer.observe(scope, {childList: true, subtree: true});
  setTimeout(() => observer.disconnect(), CONTENT_WAIT);
}
