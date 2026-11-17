/**
 * Escape presses an overlay has already closed on. Nested overlays share this,
 * so pressing Escape once closes only the innermost one.
 */
const handled = new WeakSet<Event>();

export function claimEscape(event: Event): void {
  handled.add(event);
}

export function isEscapeClaimed(event: Event): boolean {
  return handled.has(event);
}
