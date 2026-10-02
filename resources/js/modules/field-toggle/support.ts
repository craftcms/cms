import type {FieldToggle} from './field-toggle';

/**
 * Instance registry — the jQuery-free mirror of the legacy
 * `$toggle.data('fieldtoggle')` back-reference, keyed by the toggle element.
 * Modern consumers resolve the instance from here; the jQuery `.data()` handle is
 * still set too (see `field-toggle.ts`) for the still-legacy readers.
 */
export const fieldToggleData = new WeakMap<Element, FieldToggle>();
