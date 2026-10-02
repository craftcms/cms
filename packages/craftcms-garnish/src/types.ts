/**
 * Shared Garnish core types.
 */

/** Base settings shape. Subclasses extend this with their own typed settings. */
export interface GarnishBaseSettings {
  [key: string]: unknown;
}

/**
 * A generic event/settings callback. Used for the `onX` setting hooks across
 * Garnish components (e.g. `onShow`, `onChange`). A no-arg `() => void` is
 * assignable to this, so stricter callbacks are covered too.
 */
export type Callback = (...args: unknown[]) => void;

/**
 * Anything `addListener` / the DOM-listener registry will accept as an element
 * argument. jQuery is intentionally absent; the compat layer coerces jQuery
 * collections into one of these before calling core.
 */
export type ElementInput =
  | EventTarget
  | EventTarget[]
  | NodeListOf<Element>
  | string
  | null
  | undefined;

/** Generic constructor type used by the class-level event bus. */
export type Constructor<T = object> = abstract new (...args: any[]) => T;
