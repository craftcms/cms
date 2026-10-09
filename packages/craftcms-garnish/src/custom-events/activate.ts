/**
 * Garnish `activate` custom event.
 *
 * Native replacement for the legacy `$.event.special.activate`. Wires
 * mousedown/click/keydown listeners that dispatch a synthetic `activate`
 * CustomEvent, preserving the legacy focus/preventDefault behavior.
 */

import {RETURN_KEY, SPACE_KEY} from '../constants';
import {globals} from '../globals';
import {isCtrlKeyPressed} from '../utils/env';
import {hasAttr} from '../utils/dom';

// Track installs so installation is idempotent per element.
const installed = new WeakMap<
  HTMLElement,
  {count: number; dispose: () => void}
>();

function isButtonish(el: HTMLElement): boolean {
  return el.nodeName === 'BUTTON' || el.getAttribute('role') === 'button';
}

function dispatchActivate(el: HTMLElement, originalEvent: Event): void {
  const event = new CustomEvent('activate', {
    bubbles: true,
    cancelable: true,
    detail: {originalEvent},
  });
  // Expose `originalEvent` directly (legacy shape).
  (event as unknown as {originalEvent: Event}).originalEvent = originalEvent;
  el.dispatchEvent(event);
}

/**
 * Attach Garnish `activate` semantics to an element: a unified click/keyboard
 * "activate" event (Space/Enter or click) that respects disabled state, manages
 * `tabindex`, and avoids hijacking modifier-clicks on real links. Dispatched as
 * a native `activate` `CustomEvent`. Usually reached via
 * `addListener(el, 'activate', fn)` rather than called directly.
 *
 * Idempotent per element: repeated calls increment a ref-count.
 *
 * @param el - The element to make activatable.
 * @returns A disposer that decrements the ref-count and removes the listeners
 *   when the last consumer releases.
 */
export function installActivate(el: HTMLElement): () => void {
  const existing = installed.get(el);
  if (existing) {
    existing.count++;
    return makeDisposer(el);
  }

  const onMousedown = (e: MouseEvent): void => {
    // Prevent buttons from getting focus on click.
    if (isButtonish(e.currentTarget as HTMLElement)) {
      e.preventDefault();
    }
  };

  const onClick = (e: MouseEvent): void => {
    if (globals.activateEventsMuted) {
      return;
    }

    const disabled = el.classList.contains('disabled');

    // Don't interfere with Ctrl/⌘-clicks on real links.
    if (
      !disabled &&
      el.nodeName === 'A' &&
      hasAttr(el, 'href') &&
      !['#', ''].includes(el.getAttribute('href') ?? '') &&
      isCtrlKeyPressed(e)
    ) {
      return;
    }

    if (isButtonish(e.currentTarget as HTMLElement)) {
      e.preventDefault();
    }

    if (!disabled) {
      dispatchActivate(el, e);
    }
  };

  const onKeydown = (e: KeyboardEvent): void => {
    // Ignore muted, bubbled events, or non-Space/Return keys.
    if (
      globals.activateEventsMuted ||
      e.currentTarget !== el ||
      ![SPACE_KEY, RETURN_KEY].includes(e.keyCode)
    ) {
      return;
    }

    if (isButtonish(e.currentTarget as HTMLElement)) {
      e.preventDefault();
    }

    if (!el.classList.contains('disabled')) {
      dispatchActivate(el, e);
    }
  };

  el.addEventListener('mousedown', onMousedown as EventListener);
  el.addEventListener('click', onClick as EventListener);
  el.addEventListener('keydown', onKeydown as EventListener);

  // Manage tabindex, mirroring the legacy setup.
  if (!el.classList.contains('disabled')) {
    // Make focusable unless this is the body element.
    if (el !== document.body) {
      el.setAttribute('tabindex', '0');
    }
  } else if (!el.classList.contains('read-only')) {
    el.removeAttribute('tabindex');
  }

  const dispose = (): void => {
    el.removeEventListener('mousedown', onMousedown as EventListener);
    el.removeEventListener('click', onClick as EventListener);
    el.removeEventListener('keydown', onKeydown as EventListener);
  };

  installed.set(el, {count: 1, dispose});
  return makeDisposer(el);
}

function makeDisposer(el: HTMLElement): () => void {
  let called = false;
  return () => {
    if (called) {
      return;
    }
    called = true;
    const record = installed.get(el);
    if (!record) {
      return;
    }
    record.count--;
    if (record.count <= 0) {
      record.dispose();
      installed.delete(el);
    }
  };
}
