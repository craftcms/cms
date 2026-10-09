/**
 * Garnish `resize` custom event.
 *
 * Native replacement for the legacy `$.event.special.resize`. The window uses
 * the native `resize` event; other elements share a single lazily-created
 * `ResizeObserver` and only dispatch when their dimensions actually change and
 * resize events aren't muted.
 */

import {globals} from '../globals';

interface SizeRecord {
  width: number;
  height: number;
}

// Per-observed-element last-known size.
const sizes = new WeakMap<Element, SizeRecord>();

let resizeObserver: ResizeObserver | undefined;

function getResizeObserver(): ResizeObserver {
  if (!resizeObserver) {
    resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const size = sizes.get(entry.target);
        if (!size) {
          continue;
        }
        const {width, height} = entry.target.getBoundingClientRect();
        if (width !== size.width || height !== size.height) {
          sizes.set(entry.target, {width, height});
          if (!globals.resizeEventsMuted) {
            entry.target.dispatchEvent(
              new CustomEvent('resize', {bubbles: false, cancelable: false})
            );
          }
        }
      }
    });
  }
  return resizeObserver;
}

const installed = new WeakMap<
  EventTarget,
  {count: number; dispose: () => void}
>();

/**
 * Attach `resize` semantics to an element (or `window`): a `resize`
 * `CustomEvent` that fires when the element's dimensions actually change, backed
 * by a shared `ResizeObserver` (the window uses the native `resize` event).
 * Suppressed while resize events are muted (see `muteResizeEvents`). Usually
 * reached via `addListener(el, 'resize', fn)`. Idempotent per target
 * (ref-counted).
 *
 * @param el - The element (or window) to observe.
 * @returns A disposer that unobserves when the last consumer releases.
 */
export function installResize(el: HTMLElement): () => void {
  const existing = installed.get(el);
  if (existing) {
    existing.count++;
    return makeDisposer(el);
  }

  // The window natively supports `resize`; nothing to install.
  if ((el as unknown) === window) {
    installed.set(el, {count: 1, dispose: () => {}});
    return makeDisposer(el);
  }

  const {width, height} = el.getBoundingClientRect();
  sizes.set(el, {width, height});
  getResizeObserver().observe(el);

  const dispose = (): void => {
    getResizeObserver().unobserve(el);
    sizes.delete(el);
  };

  installed.set(el, {count: 1, dispose});
  return makeDisposer(el);
}

function makeDisposer(el: EventTarget): () => void {
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
