/**
 * Runs a callback once the browser is idle. Without `requestIdleCallback`,
 * the next task stands in for idle time.
 *
 * @returns A handle for {@link cancelIdle}.
 */
export function whenIdle(callback: () => void): number {
  return typeof requestIdleCallback === 'function'
    ? requestIdleCallback(callback)
    : window.setTimeout(callback);
}

/** Cancels a callback {@link whenIdle} hasn't run yet. */
export function cancelIdle(handle: number): void {
  if (typeof cancelIdleCallback === 'function') {
    cancelIdleCallback(handle);
  } else {
    window.clearTimeout(handle);
  }
}
