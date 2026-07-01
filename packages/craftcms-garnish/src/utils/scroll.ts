/**
 * Scroll-container utilities (jQuery-free).
 *
 * The drag foundation needs a shared, axis-aware "nearest scrollable ancestor"
 * finder to replace jQuery's `.scrollParent()`. It lives here (not inside
 * `base-drag.ts` or `animation.ts`) so it is a single source of truth that both
 * the drag system and `scrollContainerToElement` reuse.
 */

import {X_AXIS, Y_AXIS} from '../constants';
import {bod, doc, win} from '../globals';

/** Whether an element scrolls overflow content on at least one axis. */
function isScrollable(el: Element): boolean {
  const style = window.getComputedStyle(el);
  const overflow = `${style.overflow}${style.overflowY}${style.overflowX}`;
  return /(auto|scroll|overlay)/.test(overflow);
}

/** Whether an element scrolls overflow content on the given axis (or either). */
function isScrollableOnAxis(el: Element, axis?: 'x' | 'y' | null): boolean {
  const verticallyScrollable =
    axis !== X_AXIS && el.scrollHeight > el.clientHeight;
  const horizontallyScrollable =
    axis !== Y_AXIS && el.scrollWidth > el.clientWidth;
  return verticallyScrollable || horizontallyScrollable;
}

/**
 * Find the nearest scrollable ancestor of an element (the native, axis-aware
 * replacement for jQuery's `.scrollParent()` used by `BaseDrag`).
 *
 * The walk mirrors the legacy `BaseDrag.setScrollContainer`:
 *
 * 1. Climb from `el` to the nearest ancestor whose computed `overflow` is
 *    `auto`/`scroll`/`overlay`.
 * 2. If that ancestor is `<html>` or `<body>`, the container is the window.
 * 3. Otherwise, if it is scrollable **on the relevant axis** (has overflowing
 *    content), it is the container.
 * 4. Else, keep climbing.
 *
 * @param el - The element whose scroll container we want.
 * @param axis - Restrict the scrollability test to one axis (`'x'`/`'y'`), or
 *   `null`/omitted to accept either.
 * @returns The scrollable ancestor element, or `window` when the page itself
 *   (or `<html>`/`<body>`) is the scroller.
 */
export function getScrollParent(
  el: Element,
  axis?: 'x' | 'y' | null
): Element | Window {
  let node: Element | null = el.parentElement;

  while (node) {
    // <html>/<body> normalize to the window.
    if (node === doc.documentElement || node === bod) {
      return win;
    }

    if (isScrollable(node) && isScrollableOnAxis(node, axis)) {
      return node;
    }

    node = node.parentElement;
  }

  return win;
}

/** True if the resolved scroll container is the window. */
export function isWindowScrollContainer(container: Element | Window): boolean {
  return container === win;
}
