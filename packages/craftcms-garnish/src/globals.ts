/**
 * Garnish shared global state.
 *
 * Native references replacing the legacy jQuery collections (`$win`/`$doc`/
 * `$bod`/`$scrollContainer`), mutable feature flags, and the single shared
 * class-level event bus instance.
 *
 * The compat layer re-exposes the jQuery-wrapped forms (`$win` etc.).
 */

import {ClassEventBus} from './events';

/** `window` (compat exposes `$win = $(window)`). */
export const win: Window = window;

/** `document` (compat exposes `$doc = $(document)`). */
export const doc: Document = document;

/** `document.body` (compat exposes `$bod = $(document.body)`). */
export const bod: HTMLElement = document.body;

/**
 * The scroll container. Defaults to `window`; legacy `$scrollContainer` could be
 * reassigned to another element. Kept mutable for parity.
 */
export const globals = {
  scrollContainer: window as EventTarget,
  activateEventsMuted: false,
  resizeEventsMuted: false,
  // Derived from `document.body`'s `rtl` class at module init.
  rtl: document.body.classList.contains('rtl'),
  get ltr(): boolean {
    return !this.rtl;
  },
};

/**
 * The single shared class-level event bus. `Base.trigger` dispatches into it,
 * and `Garnish.on/off/once` proxy to it.
 */
export const garnishClassBus = new ClassEventBus();
