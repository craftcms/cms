import type {Slideout} from './slideout';

/**
 * Maps a slideout's container element back to its {@link Slideout} instance —
 * a native complement to the legacy `this.$container.data('slideout', this)`
 * back-reference, which stays in place too (see `slideout.ts`'s {@link init} —
 * `ElementEditor.js`, `CP.js`, and `CpScreenSlideout` internals all still read
 * it via jQuery `.data('slideout')`). Keyed on `this.$container[0]`, mirroring
 * the other modules' `support.ts` files.
 */
export const containerSlideouts = new WeakMap<Element, Slideout>();
