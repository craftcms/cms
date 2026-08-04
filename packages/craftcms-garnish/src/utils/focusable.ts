/**
 * Native focusable-element matcher.
 *
 * The highest-risk reimplementation in the core: jQuery-UI's `:focusable` /
 * `:tabbable` selectors have no built-in DOM equivalent. This models the
 * well-known jQuery-UI algorithm closely to preserve behavior for focus traps
 * and modal accessibility.
 *
 * Reference: jQuery-UI `focusable(element, hasTabindex)`:
 *   - <a>/<area> with href, or any element with a tabindex, are focusable when visible.
 *   - <input>/<select>/<textarea>/<button> are focusable when not disabled, visible,
 *     and (for inputs) not an unchecked radio in a same-name group with no focusable member.
 *   - everything else needs an explicit tabindex.
 *   - all of the above also require visibility.
 */

/**
 * Whether an element is visible.
 *
 * jQuery's `:visible` is "has layout boxes": `offsetWidth || offsetHeight ||
 * getClientRects().length`, plus we additionally exclude `visibility:hidden`
 * (jQuery-UI's `visible()` does this via the `visibility` check on the element
 * and its ancestors).
 */
export function isVisible(el: Element): boolean {
  const html = el as HTMLElement;

  // `display:none` (on the element or an ancestor) yields no layout boxes.
  const hasBoxes =
    html.offsetWidth > 0 ||
    html.offsetHeight > 0 ||
    el.getClientRects().length > 0;

  if (!hasBoxes) {
    return false;
  }

  // `visibility:hidden`/`collapse` on the element or any ancestor hides it.
  let node: Element | null = el;
  while (node) {
    const visibility = window.getComputedStyle(node).visibility;
    if (visibility === 'hidden' || visibility === 'collapse') {
      return false;
    }
    node = node.parentElement;
  }

  return true;
}

const FOCUSABLE_FORM_TAGS = new Set(['input', 'select', 'textarea', 'button']);

/**
 * Whether an element is focusable (visible + not disabled + naturally or
 * explicitly focusable). Negative tabindex still counts as focusable here
 * (it's only excluded for *keyboard* focus).
 */
export function isFocusable(el: Element): boolean {
  if (!(el instanceof HTMLElement) && !(el instanceof SVGElement)) {
    // areas are SVG-unrelated but covered below; restrict to real elements.
  }

  const nodeName = el.nodeName.toLowerCase();
  const tabindexAttr = el.getAttribute('tabindex');
  const hasTabindex = tabindexAttr !== null;
  const tabindex = hasTabindex ? Number(tabindexAttr) : null;

  let focusable: boolean;

  if (nodeName === 'area') {
    // <area> is focusable only when it has an href and its <map>/<img> is shown.
    const area = el as HTMLAreaElement;
    const hasHref = !!area.getAttribute('href');
    if (!hasHref) {
      return false;
    }
    focusable = true;
  } else if (FOCUSABLE_FORM_TAGS.has(nodeName)) {
    focusable = !(el as HTMLInputElement).disabled;
  } else if (nodeName === 'a') {
    // Links are focusable with an href, or if they carry a tabindex.
    focusable = !!el.getAttribute('href') || hasTabindex;
  } else {
    // Everything else needs an explicit, numeric tabindex.
    focusable = hasTabindex && !Number.isNaN(tabindex);
  }

  return focusable && isVisible(el);
}

/**
 * Whether the element is focusable by keyboard — i.e. focusable AND not
 * `tabindex="-1"` (matches legacy `isKeyboardFocusable`).
 */
export function isKeyboardFocusable(el: Element): boolean {
  if (!isFocusable(el)) {
    return false;
  }
  return el.getAttribute('tabindex') !== '-1';
}

/**
 * All focusable elements **within** a container, in document order. Equivalent
 * to legacy `$(container).find(':focusable')` (the container itself is not
 * included, matching jQuery's `.find()`).
 *
 * @param container - The element to search.
 * @returns The focusable descendants, in document order.
 */
export function getFocusableElements(container: Element): HTMLElement[] {
  // `find` excludes the container itself; match jQuery's `.find()` semantics.
  return Array.from(container.querySelectorAll<HTMLElement>('*')).filter((el) =>
    isFocusable(el)
  );
}
