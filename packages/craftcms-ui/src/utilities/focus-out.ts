/**
 * Whether a `focusout` leaves focus within any of `regions`.
 *
 * Follows the composed tree, as rendering does: from where focus went, up
 * through the slot it's assigned to, its parent, and the host of its shadow
 * root. So content slotted into a region counts as inside it, even when it
 * belongs to an outer component's light DOM, as a `craft-nav-item`'s subnav
 * does inside its flyout. Focus moving to nothing (`relatedTarget` is `null`,
 * as when someone clicks text) isn't within a region, so each caller decides
 * what that means for it.
 */
export function focusStaysWithin(
  event: FocusEvent,
  ...regions: Array<Node | null | undefined>
): boolean {
  const moved = event.relatedTarget;
  if (!(moved instanceof Node)) {
    return false;
  }

  for (let node: Node | null = moved; node; node = composedParent(node)) {
    if (regions.includes(node)) {
      return true;
    }
  }

  return false;
}

/** The node `node` renders inside: its slot, its parent, or its shadow host. */
function composedParent(node: Node): Node | null {
  const slot = 'assignedSlot' in node ? (node as Element).assignedSlot : null;
  if (slot) {
    return slot;
  }

  const parent = node.parentNode;
  return parent instanceof ShadowRoot ? parent.host : parent;
}
