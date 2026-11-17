/**
 * Focus management utilities (jQuery-free).
 */

import {TAB_KEY} from '../constants';
import {
  getFocusableElements,
  isKeyboardFocusable,
  isVisible,
} from './focusable';

/**
 * Whether keyboard focus currently lives inside (but is not) a container.
 *
 * @param container - The container to test.
 * @returns `true` if the active element is a descendant of `container`.
 */
export function focusIsInside(container: Element): boolean {
  const active = document.activeElement;
  return active !== null && container.contains(active) && active !== container;
}

/**
 * The first focusable element inside a container, or `null` if none.
 *
 * @param container - The container to search.
 */
export function firstFocusableElement(container: Element): HTMLElement | null {
  return getFocusableElements(container)[0] ?? null;
}

/**
 * All keyboard-focusable (Tab-reachable) elements inside a container, in
 * document order. Excludes `tabindex="-1"` elements.
 *
 * @param container - The container to search.
 */
export function getKeyboardFocusableElements(
  container: Element
): HTMLElement[] {
  return getFocusableElements(container).filter((el) =>
    isKeyboardFocusable(el)
  );
}

/**
 * The currently focused element, or `null`.
 *
 * @returns `document.activeElement`.
 * @remarks The modern core returns a native element; the compat layer wraps it
 *   in a jQuery `:focus` collection to match the legacy shape.
 */
export function getFocusedElement(): Element | null {
  return document.activeElement;
}

// Tracks the `keydown` handler bound by `trapFocusWithin` per container so
// `releaseFocusWithin` can remove exactly it (replaces the `.focus-trap` namespace).
const focusTrapHandlers = new WeakMap<Element, (ev: KeyboardEvent) => void>();

/**
 * Trap keyboard focus within a container: tabbing past the last Tab stop
 * wraps to the first, and Shift-Tab past the first wraps to the last.
 * Replaces any trap previously installed on the same container.
 *
 * The stops are the keyboard-focusable elements in the order they render,
 * including the controls a component renders in its open shadow root and the
 * content it slots in. An element that's only focused from code, such as a
 * heading with `tabindex="-1"`, isn't one, and focus on something before the
 * first stop or after the last wraps the same way.
 *
 * Pair with {@link releaseFocusWithin} to remove the trap (e.g. when a modal
 * closes).
 *
 * @param container - The element to confine Tab navigation within.
 */
export function trapFocusWithin(container: Element): void {
  releaseFocusWithin(container);

  const handler = (ev: KeyboardEvent): void => {
    if (ev.keyCode !== TAB_KEY) {
      return;
    }

    const elements = renderedElements(container);
    const firstIndex = elements.findIndex((el) => isKeyboardFocusable(el));
    if (firstIndex === -1) {
      return;
    }
    let lastIndex = elements.length - 1;
    while (!isKeyboardFocusable(elements[lastIndex]!)) {
      lastIndex--;
    }

    // The control itself, not the component its event was retargeted to. The
    // container isn't in the list, so focus parked on it counts as before the
    // first stop.
    const targetIndex = elements.indexOf(ev.composedPath()[0] as Element);

    if (ev.shiftKey && targetIndex <= firstIndex) {
      ev.preventDefault();
      ev.stopPropagation();
      (elements[lastIndex] as HTMLElement).focus();
    } else if (!ev.shiftKey && targetIndex >= lastIndex) {
      ev.preventDefault();
      ev.stopPropagation();
      (elements[firstIndex] as HTMLElement).focus();
    }
  };

  container.addEventListener('keydown', handler as EventListener);
  focusTrapHandlers.set(container, handler);
}

/**
 * Every element inside `container` in the order it renders: a component's
 * open shadow root in place of its children, and each slot's assigned
 * elements (or its fallback content) where the slot is. A child no slot takes
 * isn't rendered, so it isn't included.
 *
 * Keep in sync with `renderedElements()` and `trapFocusWithin()` in
 * `packages/craftcms-legacy/garnish/src/Garnish.js`, which can't import this.
 */
function renderedElements(container: Element): Element[] {
  const elements: Element[] = [];

  const visitChildren = (parent: Element): void => {
    let children: Iterable<Element> =
      parent.shadowRoot?.children ?? parent.children;
    // Assigned text hides the fallback content too, so check nodes, not elements.
    if (
      parent instanceof HTMLSlotElement &&
      parent.assignedNodes({flatten: true}).length
    ) {
      children = parent.assignedElements({flatten: true});
    }
    for (const child of children) {
      elements.push(child);
      visitChildren(child);
    }
  };

  visitChildren(container);
  return elements;
}

/**
 * Release a focus trap previously installed by {@link trapFocusWithin}. No-op if
 * none is active on the container.
 *
 * @param container - The trapped container.
 */
export function releaseFocusWithin(container: Element): void {
  const handler = focusTrapHandlers.get(container);
  if (handler) {
    container.removeEventListener('keydown', handler as EventListener);
    focusTrapHandlers.delete(container);
  }
}

/**
 * Move focus to the first sensible focusable element within a container, or to
 * the container itself (made focusable with `tabindex="-1"`) when none is
 * suitable. No-op if focus is already inside.
 *
 * Skips `.checkbox` and `.prevent-autofocus` elements, and preserves the
 * `.field:visible` heuristic from cms#15245: if the first focusable element
 * isn't inside the first visible `.field`, autofocus is skipped in favor of the
 * container. Used by {@link Modal} on show.
 *
 * @param container - The container to focus within.
 */
export function setFocusWithin(container: Element): void {
  // Already contains focus? Do nothing.
  if (
    document.activeElement &&
    container.contains(document.activeElement) &&
    document.activeElement !== container
  ) {
    return;
  }

  // First focusable that isn't a .checkbox or .prevent-autofocus.
  let firstFocusable: HTMLElement | null =
    getFocusableElements(container).find(
      (el) =>
        !el.classList.contains('checkbox') &&
        !el.classList.contains('prevent-autofocus')
    ) ?? null;

  // First *visible* `.field` in the container.
  const firstVisibleField =
    Array.from(container.querySelectorAll('.field')).find((el) =>
      isVisible(el)
    ) ?? null;

  // The `.field` ancestor of our first focusable.
  const focusableField = firstFocusable
    ? firstFocusable.closest('.field')
    : null;

  // If the first visible field isn't the first focusable's field, bail on autofocus.
  if (firstVisibleField !== focusableField) {
    firstFocusable = null;
  }

  if (firstFocusable) {
    firstFocusable.focus();
  } else {
    container.setAttribute('tabindex', '-1');
    (container as HTMLElement).focus();
  }
}
