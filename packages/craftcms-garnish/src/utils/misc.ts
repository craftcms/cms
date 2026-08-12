/**
 * Miscellaneous pure utilities.
 */

import {RETURN_KEY, SPACE_KEY, TEXT_NODE} from '../constants';

/**
 * Whether a value is a "plain object" — the native replacement for jQuery's
 * `$.isPlainObject`, used by the drag classes' `(items, settings)` param-shift to
 * detect a settings object passed as the first argument.
 *
 * Excludes DOM nodes, arrays, array-likes (anything with a numeric `length`), and
 * event targets, so an element / element list passed as `items` is never mistaken
 * for settings.
 */
export function isPlainObject(val: unknown): val is Record<string, unknown> {
  return (
    typeof val === 'object' &&
    val !== null &&
    !(val instanceof Element) &&
    !(typeof Node !== 'undefined' && val instanceof Node) &&
    !Array.isArray(val) &&
    typeof (val as {length?: unknown}).length !== 'number' &&
    typeof (val as EventTarget).addEventListener !== 'function' &&
    (Object.getPrototypeOf(val) === Object.prototype ||
      Object.getPrototypeOf(val) === null)
  );
}

/** Euclidean distance between two coordinates. */
export function getDist(
  x1: number,
  y1: number,
  x2: number,
  y2: number
): number {
  return Math.sqrt(Math.pow(x1 - x2, 2) + Math.pow(y1 - y2, 2));
}

/** Clamp a number into a [min, max] range. */
export function within(num: number, min: number, max: number): number {
  num = Math.max(num, min);
  num = Math.min(num, max);
  return num;
}

/** Whether a value is a string. */
export function isString(val: unknown): val is string {
  return typeof val === 'string';
}

/**
 * Whether a value is an array.
 * @deprecated Use `Array.isArray` directly.
 */
export function isArray(val: unknown): val is unknown[] {
  return Array.isArray(val);
}

/** Whether a node is a text node. */
export function isTextNode(elem: Node): boolean {
  return elem.nodeType === TEXT_NODE;
}

/**
 * Logs a message to the console, if available.
 * @deprecated Use `console` directly.
 */
export function log(msg: unknown): void {
  if (typeof console !== 'undefined' && typeof console.log === 'function') {
    console.log(msg);
  }
}

/**
 * Resolves once `test()` returns a truthy value, calling it again every
 * `delay` milliseconds in the meantime.
 *
 * `test` may return a promise; its resolved value is awaited before being
 * checked for truthiness, and the next call isn’t scheduled until it settles.
 * A rejection/throw from `test()` propagates, rejecting the returned promise.
 *
 * @param test A function to call repeatedly until it returns (or resolves to) a truthy value.
 * @param delay The interval, in milliseconds, between calls (default `100`).
 * @param signal An optional `AbortSignal`. If already aborted, or aborted while
 * waiting for the next call, the returned promise rejects with `signal.reason`
 * instead of continuing to poll — use this to stop polling once a caller (e.g.
 * an owning class' `destroy()`) no longer cares about the result.
 * @returns A promise that resolves with `test()`’s truthy return value.
 */
export async function deferUntil<T>(
  test: () => T | Promise<T>,
  delay = 100,
  signal?: AbortSignal
): Promise<T> {
  if (signal?.aborted) {
    throw signal.reason;
  }
  const result = await test();
  if (result) {
    return result;
  }
  await new Promise<void>((resolve, reject) => {
    const timer = setTimeout(resolve, delay);
    signal?.addEventListener(
      'abort',
      () => {
        clearTimeout(timer);
        reject(signal.reason);
      },
      {once: true}
    );
  });
  return deferUntil(test, delay, signal);
}

/**
 * Space/Enter → preventDefault + callback.
 * @deprecated The `activate` event should be used instead.
 */
export function handleActivatingKeypress(
  event: KeyboardEvent,
  callback: () => void
): void {
  const key = event.keyCode;
  if (key === SPACE_KEY || key === RETURN_KEY) {
    event.preventDefault();
    callback();
  }
}
