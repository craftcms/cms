/**
 * The single documented jQuery seam for `resources/js`.
 *
 * Ported CP code is jQuery-free except where it must interoperate with the
 * still-legacy bundle — reading or writing a legacy `.data()` cache, or calling
 * a `$.fn` plugin that hasn't been ported yet. Those spots go through here, so
 * every remaining jQuery dependency in `resources/js` is greppable to one
 * accessor and disappears the day the corresponding legacy piece is ported.
 *
 * Returns the jQuery global (`window.jQuery`, aliased as `$`) if the CP bundle
 * has loaded it, else `null`.
 */
export function jq(): JQueryStatic | null {
  return window.jQuery ?? window.$ ?? null;
}
