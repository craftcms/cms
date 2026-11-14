import {composedParent} from './focus-out.js';

/**
 * Presses outside an overlay that it has already closed on, keyed by the
 * press's `pointerdown`. Nested overlays share this, so pressing outside them
 * once closes only the innermost one, as Escape does.
 */
const claims = new WeakMap<Event, Element[]>();

export function claimOutsidePress(event: Event, overlay: Element): void {
  claims.set(event, [...(claims.get(event) ?? []), overlay]);
}

/**
 * Whether an overlay rendered inside `region`, following the composed tree,
 * already closed on the press `event` started.
 */
export function isOutsidePressClaimedWithin(
  event: Event,
  region: Node
): boolean {
  return (claims.get(event) ?? []).some((overlay) => {
    for (
      let node: Node | null = composedParent(overlay);
      node;
      node = composedParent(node)
    ) {
      if (node === region) {
        return true;
      }
    }

    return false;
  });
}
