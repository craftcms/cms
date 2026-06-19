/**
 * ARIA / modal-background utilities (jQuery-free).
 */

import {
  JS_ARIA_CLASS,
  JS_ARIA_FALSE_CLASS,
  JS_ARIA_TRUE_CLASS,
} from '../constants';
import {getUiLayerManager} from '../managers/registry';

/**
 * Add the modal ARIA + role attributes (`aria-modal="true"`, `role="dialog"`) to
 * a container. Called by {@link Modal} when its container is set.
 *
 * @param container - The modal container element.
 */
export function addModalAttributes(container: Element): void {
  container.setAttribute('aria-modal', 'true');
  container.setAttribute('role', 'dialog');
}

/** Whether an element is a `<script>` or `<style>`. */
export function isScriptOrStyleElement(element: Element): boolean {
  return element.tagName === 'SCRIPT' || element.tagName === 'STYLE';
}

/** Whether an element carries one of the JS-ARIA bookkeeping classes. */
export function hasJsAriaClass(element: Element): boolean {
  return (
    element.classList.contains(JS_ARIA_CLASS) ||
    element.classList.contains(JS_ARIA_FALSE_CLASS) ||
    element.classList.contains(JS_ARIA_TRUE_CLASS)
  );
}

/**
 * Apply `aria-hidden="true"` to an element, recording its prior value via a
 * bookkeeping class so {@link resetModalBackgroundLayerVisibility} can restore
 * it exactly.
 *
 * @param element - The element to hide from assistive technology.
 */
export function ariaHide(element: Element): void {
  const prior = element.getAttribute('aria-hidden');

  if (!prior) {
    element.classList.add(JS_ARIA_CLASS);
  } else if (prior === 'false') {
    element.classList.add(JS_ARIA_FALSE_CLASS);
  } else if (prior === 'true') {
    element.classList.add(JS_ARIA_TRUE_CLASS);
  }

  element.setAttribute('aria-hidden', 'true');
}

/**
 * Hide every immediate child of `<body>` from assistive technology (via
 * {@link ariaHide}), except `#notifications`, the topmost UI layer's container,
 * and `<script>`/`<style>` elements. Used by {@link Modal} on show to background
 * the rest of the page.
 */
export function hideModalBackgroundLayers(): void {
  const manager = getUiLayerManager();
  const topmostLayer = manager?.currentLayer.$container ?? null;

  for (const child of Array.from(document.body.children)) {
    if (child.id === 'notifications') {
      continue;
    }

    // If element is the topmost layer or already hidden, do nothing.
    if (hasJsAriaClass(child) || child === topmostLayer) {
      continue;
    }

    if (!isScriptOrStyleElement(child)) {
      ariaHide(child);
    }
  }
}

/**
 * Reverse {@link hideModalBackgroundLayers}. If another modal layer is still
 * open, only that layer is made accessible again; otherwise every element
 * previously hidden has its prior `aria-hidden` state restored. Used by
 * {@link Modal} on hide.
 */
export function resetModalBackgroundLayerVisibility(): void {
  const manager = getUiLayerManager();
  const highestModalLayer = manager?.highestModalLayer;

  const hiddenLayerClasses = [
    JS_ARIA_CLASS,
    JS_ARIA_TRUE_CLASS,
    JS_ARIA_FALSE_CLASS,
  ];

  // If there is another modal, make it accessible to AT and stop.
  if (highestModalLayer && highestModalLayer.$container) {
    const container = highestModalLayer.$container;
    container.classList.remove(...hiddenLayerClasses);
    container.removeAttribute('aria-hidden');
    return;
  }

  // Otherwise, restore every element we hid.
  const selector = hiddenLayerClasses.map((name) => `.${name}`).join(', ');
  const hiddenElements = document.querySelectorAll(selector);

  for (const el of Array.from(hiddenElements)) {
    if (el.classList.contains(JS_ARIA_CLASS)) {
      el.classList.remove(JS_ARIA_CLASS);
      el.removeAttribute('aria-hidden');
    } else if (el.classList.contains(JS_ARIA_FALSE_CLASS)) {
      el.classList.remove(JS_ARIA_FALSE_CLASS);
      el.setAttribute('aria-hidden', 'false');
    } else if (el.classList.contains(JS_ARIA_TRUE_CLASS)) {
      el.classList.remove(JS_ARIA_TRUE_CLASS);
      el.setAttribute('aria-hidden', 'true');
    }
  }
}
