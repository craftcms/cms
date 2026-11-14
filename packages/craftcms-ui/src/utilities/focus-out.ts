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

/**
 * Whether `node` is `ancestor` or renders inside it, following the composed
 * tree: through slots, parents and shadow hosts.
 */
export function composedContains(ancestor: Node, node: Node): boolean {
  for (
    let current: Node | null = node;
    current;
    current = composedParent(current)
  ) {
    if (current === ancestor) {
      return true;
    }
  }

  return false;
}

/**
 * Whether `node`, or something inside it, has focus. Reads the active element
 * of `node`'s own tree, so it works for a node inside a shadow root.
 */
export function containsFocus(node: Node): boolean {
  const root = node.getRootNode() as Document | ShadowRoot;
  const active = root.activeElement;
  return Boolean(active && (active === node || node.contains(active)));
}

/** The node `node` renders inside: its slot, its parent, or its shadow host. */
export function composedParent(node: Node): Node | null {
  const slot = 'assignedSlot' in node ? (node as Element).assignedSlot : null;
  if (slot) {
    return slot;
  }

  const parent = node.parentNode;
  return parent instanceof ShadowRoot ? parent.host : parent;
}
