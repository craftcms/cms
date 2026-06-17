/**
 * Miscellaneous pure utilities.
 */

import {RETURN_KEY, SPACE_KEY, TEXT_NODE} from '../constants';

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
