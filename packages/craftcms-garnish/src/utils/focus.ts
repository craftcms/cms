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
 * Trap keyboard focus within a container: tabbing past the last focusable
 * element wraps to the first, and Shift-Tab past the first wraps to the last.
 * Replaces any trap previously installed on the same container.
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

    const focusable = getFocusableElements(container);
    if (focusable.length === 0) {
      return;
    }

    const index = focusable.indexOf(ev.target as HTMLElement);

    if (index === 0 && ev.shiftKey) {
      ev.preventDefault();
      ev.stopPropagation();
      focusable[focusable.length - 1]!.focus();
    } else if (index === focusable.length - 1 && !ev.shiftKey) {
      ev.preventDefault();
      ev.stopPropagation();
      focusable[0]!.focus();
    }
  };

  container.addEventListener('keydown', handler as EventListener);
  focusTrapHandlers.set(container, handler);
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
