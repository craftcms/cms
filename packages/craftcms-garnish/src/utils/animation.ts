/**
 * Animation / scroll utilities (jQuery-free; Velocity removed).
 *
 * `shake` uses the Web Animations API; `scrollContainerToElement` uses manual
 * `scrollTop` math. Both respect `prefers-reduced-motion`.
 */

import {SHAKE_STEP_DURATION, SHAKE_STEPS} from '../constants';
import {doc, globals, win} from '../globals';

/** Re-export of the native RAF (vendor prefixes are long dead). */
export const requestAnimationFrame: typeof window.requestAnimationFrame =
  window.requestAnimationFrame.bind(window);

export const cancelAnimationFrame: typeof window.cancelAnimationFrame =
  window.cancelAnimationFrame.bind(window);

/** Whether the user prefers reduced motion. */
export function prefersReducedMotion(): boolean {
  const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  // Legacy returns true if the query is unavailable.
  return !mediaQuery || mediaQuery.matches;
}

/**
 * `0` if the user prefers reduced motion, otherwise the given duration.
 */
export function getUserPreferredAnimationDuration(
  duration: number | string
): number | string {
  return prefersReducedMotion() ? 0 : duration;
}

/**
 * Finds the nearest scrollable ancestor of an element (native replacement for
 * jQuery's `.scrollParent()`).
 */
function getScrollParent(el: Element): Element | Window {
  let node: Element | null = el.parentElement;
  while (node) {
    const style = window.getComputedStyle(node);
    const overflowY = style.overflowY;
    if (
      (overflowY === 'auto' ||
        overflowY === 'scroll' ||
        overflowY === 'overlay') &&
      node.scrollHeight > node.clientHeight
    ) {
      return node;
    }
    node = node.parentElement;
  }
  return win;
}

/**
 * Scrolls a container so that an element within it is in view.
 *
 * Parity with legacy `scrollContainerToElement`, minus Velocity (uses
 * instant `scrollTop` assignment / `window.scrollTo`).
 */
export function scrollContainerToElement(
  container: Element | Window,
  elem?: Element
): void {
  let target: Element;
  let scrollTarget: Element | Window;

  if (elem === undefined) {
    // Single-arg form: container is actually the element; find its scroll parent.
    target = container as Element;
    scrollTarget = getScrollParent(target);
  } else {
    scrollTarget = container;
    target = elem;
  }

  // Normalize <html>/document to window.
  if (
    scrollTarget instanceof Element &&
    (scrollTarget.nodeName === 'HTML' || scrollTarget === doc.documentElement)
  ) {
    scrollTarget = win;
  }
  if ((scrollTarget as unknown) === doc) {
    scrollTarget = win;
  }

  const isWindow = scrollTarget === win;
  const scrollTop = isWindow
    ? window.scrollY
    : (scrollTarget as Element).scrollTop;
  const elemOffsetTop = target.getBoundingClientRect().top + window.scrollY;

  let elemScrollOffset: number;
  if (isWindow) {
    elemScrollOffset = elemOffsetTop - scrollTop;
  } else {
    const containerOffsetTop =
      (scrollTarget as Element).getBoundingClientRect().top + window.scrollY;
    elemScrollOffset = elemOffsetTop - containerOffsetTop;
  }

  let targetScrollTop: number | false = false;

  if (elemScrollOffset < 0) {
    targetScrollTop = scrollTop + elemScrollOffset - 10;
  } else {
    const elemHeight = target.getBoundingClientRect().height;
    const containerHeight = isWindow
      ? window.innerHeight
      : (scrollTarget as Element).clientHeight;

    if (elemScrollOffset + elemHeight > containerHeight) {
      targetScrollTop =
        scrollTop + (elemScrollOffset - (containerHeight - elemHeight)) + 10;
    }
  }

  if (targetScrollTop !== false) {
    if (isWindow) {
      window.scrollTo({top: targetScrollTop});
    } else {
      (scrollTarget as Element).scrollTop = targetScrollTop;
    }
  }
}

/**
 * Shakes an element by animating a CSS property back and forth.
 *
 * Rewritten with the Web Animations API (Velocity removed). Respects
 * `prefers-reduced-motion` (no-op). Mirrors the legacy 10-step amplitude ramp.
 *
 * @param elem The element to shake.
 * @param prop The CSS property to adjust (default `'margin-left'`).
 */
export function shake(elem: HTMLElement, prop = 'margin-left'): void {
  if (prefersReducedMotion()) {
    return;
  }

  let startingPoint = parseInt(
    window.getComputedStyle(elem).getPropertyValue(prop),
    10
  );
  if (Number.isNaN(startingPoint)) {
    startingPoint = 0;
  }

  // Build the keyframe ramp: same offsets the legacy setTimeout loop produced.
  const keyframes: Keyframe[] = [];
  for (let i = 0; i <= SHAKE_STEPS; i++) {
    const value = startingPoint + (i % 2 ? -1 : 1) * (10 - i);
    keyframes.push({[prop]: `${value}px`});
  }

  if (typeof elem.animate === 'function') {
    elem.animate(keyframes, {
      duration: SHAKE_STEP_DURATION * SHAKE_STEPS,
      easing: 'linear',
    });
  }
}
