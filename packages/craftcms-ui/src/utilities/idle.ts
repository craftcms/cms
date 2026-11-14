/**
 * Runs a callback once the browser is idle, with the deadline for that idle
 * period. Without `requestIdleCallback`, the next task stands in for idle
 * time, and there's no deadline.
 *
 * @returns A handle for {@link cancelIdle}.
 */
export function whenIdle(callback: (deadline?: IdleDeadline) => void): number {
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
