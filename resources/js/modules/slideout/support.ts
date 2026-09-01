import type {Slideout} from './slideout';

/**
 * Maps a slideout's container element back to its {@link Slideout} instance.
 * Complements the jQuery `$container.data('slideout')` back-reference (also
 * set in `init`, and read by `ElementEditor.js`/`CP.js`). Keyed on the
 * container element, mirroring the other modules' `support.ts` files.
 */
export const containerSlideouts = new WeakMap<Element, Slideout>();
