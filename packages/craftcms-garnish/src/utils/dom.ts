/**
 * DOM utilities (jQuery-free).
 *
 * `coerceElements` is the native replacement for jQuery's `$(elem)` collection
 * coercion, used throughout the DOM-listener registry and helpers.
 */

import type {ElementInput} from '../types';
import {globals, win} from '../globals';

/**
 * Coerce any {@link ElementInput} into a flat array of `EventTarget`s — the
 * native replacement for jQuery's permissive `$(x)` collection coercion.
 *
 *  - a CSS selector string → `querySelectorAll`
 *  - a single node / window / document → `[node]`
 *  - an array / NodeList → flattened (nulls dropped)
 *  - null/undefined → `[]`
 *
 * @param input - The value to coerce.
 * @returns A flat array of event targets.
 */
export function coerceElements(input: ElementInput): EventTarget[] {
  if (input === null || input === undefined) {
    return [];
  }

  if (typeof input === 'string') {
    return Array.from(document.querySelectorAll(input));
  }

  // Array or NodeList (array-like with length, not an EventTarget itself).
  if (Array.isArray(input)) {
    return input.filter((el): el is EventTarget => el != null);
  }

  if (
    typeof (input as NodeListOf<Element>).length === 'number' &&
    typeof (input as EventTarget).addEventListener !== 'function'
  ) {
    return Array.from(input as NodeListOf<Element>).filter(
      (el): el is Element => el != null
    );
  }

  // Single EventTarget (Element, window, document, ...).
  return [input as EventTarget];
}

/** First resolved element of an `ElementInput`, or `undefined`. */
export function getElement(input: ElementInput): EventTarget | undefined {
  return coerceElements(input)[0];
}

/**
 * Returns whether an element has an attribute (present and not literally
 * `"false"`). Legacy checked `$(elem).attr(attr)` for `!== undefined && !== false`;
 * since DOM attributes are always strings, only a missing attribute is falsy.
 */
export function hasAttr(elem: Element, attr: string): boolean {
  return elem.hasAttribute(attr);
}

/**
 * The nearest sibling matching `selector` in the given direction, or `null`.
 * Skips any intervening siblings that don't match — a directional analogue of
 * `Element.closest()` scoped to siblings.
 */
export function nearestSibling(
  elem: Element,
  selector: string,
  direction: 'previous' | 'next'
): HTMLElement | null {
  const step = (node: Element): Element | null =>
    direction === 'previous'
      ? node.previousElementSibling
      : node.nextElementSibling;

  for (let sibling = step(elem); sibling; sibling = step(sibling)) {
    if (sibling.matches(selector)) {
      return sibling as HTMLElement;
    }
  }
  return null;
}

/**
 * The value registered in `registry` for the nearest ancestor of `elem`
 * (self excluded), or `null` — the native, WeakMap-keyed counterpart to
 * jQuery's `$el.closest(sel).data(key)` back-reference lookup.
 */
export function closestRegistered<T>(
  elem: Element,
  registry: WeakMap<Element, T>
): T | null {
  for (let node = elem.parentElement; node; node = node.parentElement) {
    const value = registry.get(node);
    if (value !== undefined) {
      return value;
    }
  }
  return null;
}

/**
 * Offset of an element relative to the document, adjusted for a non-window
 * scroll container (parity with legacy `getOffset`).
 */
export function getOffset(elem: Element): {top: number; left: number} {
  const rect = elem.getBoundingClientRect();
  const offset = {
    top: rect.top + window.scrollY,
    left: rect.left + window.scrollX,
  };

  if (globals.scrollContainer !== win) {
    const container = globals.scrollContainer as Element;
    if (typeof container.scrollTop === 'number') {
      offset.top += container.scrollTop;
      offset.left += container.scrollLeft;
    }
  }

  return offset;
}

/**
 * Border-box width of an element (native replacement for jQuery's
 * `.outerWidth()`). Reserved for the downstream `Drag` port, which reads
 * `this.$targetItem.outerWidth()`.
 */
export function getOuterWidth(elem: HTMLElement): number {
  return elem.offsetWidth;
}

/**
 * Border-box height of an element (native replacement for jQuery's
 * `.outerHeight()`). Reserved for the downstream `Drag` port.
 */
export function getOuterHeight(elem: HTMLElement): number {
  return elem.offsetHeight;
}

/**
 * Whether an element's bounding box contains a page-coordinate point.
 * Uses page coords (adds `scrollX/Y`) to match legacy `hitTest`.
 */
export function hitTest(x: number, y: number, elem: Element): boolean {
  const rect = elem.getBoundingClientRect();
  const x1 = rect.left + window.scrollX;
  const y1 = rect.top + window.scrollY;
  const x2 = x1 + rect.width;
  const y2 = y1 + rect.height;

  return x >= x1 && x < x2 && y >= y1 && y < y2;
}

/** Whether the cursor (mouse event page coords) is over an element. */
export function isCursorOver(
  ev: {pageX: number; pageY: number},
  elem: Element
): boolean {
  return hitTest(ev.pageX, ev.pageY, elem);
}

const TEXT_STYLE_PROPS = [
  'fontFamily',
  'fontSize',
  'fontWeight',
  'letterSpacing',
  'lineHeight',
  'textAlign',
  'textIndent',
  'whiteSpace',
  'wordSpacing',
  'wordWrap',
] as const;

/** Copies the 10 font/text CSS props from one element to another. */
export function copyTextStyles(source: HTMLElement, target: HTMLElement): void {
  const computed = window.getComputedStyle(source);
  for (const prop of TEXT_STYLE_PROPS) {
    target.style.setProperty(
      toKebabCase(prop),
      computed.getPropertyValue(toKebabCase(prop))
    );
  }
}

function toKebabCase(prop: string): string {
  return prop.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`);
}
