/**
 * Garnish constants.
 *
 * Ported verbatim from the legacy `Garnish` singleton (src/Garnish.js).
 * Plain numeric/string constants only — no jQuery dependency.
 */

import type {Callback} from './types';

// Key code constants
export const BACKSPACE_KEY = 8;
export const TAB_KEY = 9;
export const CLEAR_KEY = 12;
export const RETURN_KEY = 13;
export const SHIFT_KEY = 16;
export const CTRL_KEY = 17;
export const ALT_KEY = 18;
export const ESC_KEY = 27;
export const SPACE_KEY = 32;
export const PAGE_UP_KEY = 33;
export const PAGE_DOWN_KEY = 34;
export const END_KEY = 35;
export const HOME_KEY = 36;
export const LEFT_KEY = 37;
export const UP_KEY = 38;
export const RIGHT_KEY = 39;
export const DOWN_KEY = 40;
export const DELETE_KEY = 46;
export const A_KEY = 65;
export const S_KEY = 83;
export const CMD_KEY = 91;
export const META_KEY = 224;

// ARIA hidden classes
export const JS_ARIA_CLASS = 'garnish-js-aria';
export const JS_ARIA_TRUE_CLASS = 'garnish-js-aria-true';
export const JS_ARIA_FALSE_CLASS = 'garnish-js-aria-false';

// Mouse button constants
// NOTE: these match the legacy `event.which` numbering (1 = primary, 3 = secondary).
// Native code should prefer `MouseEvent.button` (0 = primary). Kept for compat-layer parity.
export const PRIMARY_CLICK = 1;
export const SECONDARY_CLICK = 3;

// Axis constants
export const X_AXIS = 'x';
export const Y_AXIS = 'y';

export const FX_DURATION = 200;

// Node types
export const TEXT_NODE = 3;

// Shake animation
export const SHAKE_STEPS = 10;
export const SHAKE_STEP_DURATION = 25;

/**
 * A shared no-op callback — the default for `onX` settings hooks (the modern
 * replacement for the legacy `$.noop`). Typed as {@link Callback}.
 */
export const noop: Callback = () => {};
