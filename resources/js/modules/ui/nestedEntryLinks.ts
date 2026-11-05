/** Two versions of a field with nested or related elements, such as a Matrix field: what to connect. */
export type NestedEntryScope = [HTMLElement | null, HTMLElement | null];

/** A Matrix block, a nested element’s card, or a related element’s chip. */
const ITEM_SELECTOR =
  '[data-matrix-block], .element[data-id], .element-chips__item';

/**
 * Identifies a nested element across versions: a draft’s or a revision’s
 * copy of an entry has its own ID, but carries the canonical entry’s ID.
 */
function itemKey(item: HTMLElement): string | null {
  // A chip lays out as its list item, which has the box to draw from.
  if (item.matches('.element-chips__item')) {
    return (
      item.querySelector<HTMLElement>('craft-chip[data-id]')?.dataset.id ?? null
    );
  }

  // A card's own element carries the canonical ID, under its list item.
  if (item.matches('.element[data-id]')) {
    return (
      item.querySelector<HTMLElement>('[data-canonical-id]')?.dataset
        .canonicalId ??
      item.dataset.id ??
      null
    );
  }

  return item.dataset.canonicalId ?? item.dataset.elementId ?? null;
}

/** Where an item is drawn: a chip’s own box, rather than its list item’s full width. */
function box(item: HTMLElement): DOMRect {
  const chip = item.matches('.element-chips__item')
    ? item.querySelector('craft-chip')?.shadowRoot?.querySelector('.cp-chip')
    : null;

  return (chip ?? item).getBoundingClientRect();
}

/** The field’s own nested elements, not ones nested in them. */
function items(scope: HTMLElement): Map<string, HTMLElement> {
  const found = new Map<string, HTMLElement>();

  for (const item of scope.querySelectorAll<HTMLElement>(ITEM_SELECTOR)) {
    const key = itemKey(item);

    const outer = item.parentElement?.closest(ITEM_SELECTOR);

    if (key && !(outer && scope.contains(outer))) {
      found.set(key, item);
    }
  }

  return found;
}

/**
 * The entries that kept their order: the longest run of shared entries that
 * are in the same order on both sides, preferring the run that's moved the
 * least. The rest are the ones that moved, so moving one entry to the top
 * marks just that entry, not every one after it.
 */
function unmoved(fromOrder: string[], toOrder: string[]): Set<string> {
  const positions = toOrder.map((key) => fromOrder.indexOf(key));
  const runs = positions.map((position, i) => ({
    length: 1,
    distance: Math.abs(position - i),
    previous: -1,
  }));

  positions.forEach((position, i) => {
    const run = runs[i]!;
    const own = run.distance;

    for (let j = 0; j < i; j++) {
      const before = runs[j]!;

      if (
        positions[j]! < position &&
        (before.length + 1 > run.length ||
          (before.length + 1 === run.length &&
            before.distance + own < run.distance))
      ) {
        run.length = before.length + 1;
        run.distance = before.distance + own;
        run.previous = j;
      }
    }
  });

  let i = runs.reduce(
    (best, run, index) =>
      best < 0 ||
      run.length > runs[best]!.length ||
      (run.length === runs[best]!.length && run.distance < runs[best]!.distance)
        ? index
        : best,
    -1
  );
  const kept = new Set<string>();

  while (i >= 0) {
    kept.add(toOrder[i]!);
    i = runs[i]!.previous;
  }

  return kept;
}

export interface NestedEntryLink {
  path: string;
  ends: [x: number, y: number][];
  /** The nested element on each side, which highlight the link while they’re hovered or focused. */
  items: [HTMLElement, HTMLElement];
  /** Whether the entry is in another position among the entries both versions have. */
  moved: boolean;
}

export interface NestedEntryChanges {
  moved: number;
  added: number;
  removed: number;
}

/**
 * Connects each nested element on one side to the same one on the other.
 * Sides that aren’t next to each other, such as stacked panes, get no links.
 */
export function nestedEntryLinks(
  origin: DOMRect,
  scopes: NestedEntryScope[]
): {links: NestedEntryLink[]; changes: NestedEntryChanges} {
  const links: NestedEntryLink[] = [];
  const changes = {moved: 0, added: 0, removed: 0};

  for (const [from, to] of scopes) {
    if (!from || !to) {
      continue;
    }

    const fromItems = items(from);
    const toItems = items(to);
    const shared = [...toItems.keys()].filter((key) => fromItems.has(key));
    const fromOrder = [...fromItems.keys()].filter((key) => toItems.has(key));
    const kept = unmoved(fromOrder, shared);
    const fromRight = from.getBoundingClientRect().right;
    const sideBySide = fromRight <= to.getBoundingClientRect().left;

    changes.added += toItems.size - shared.length;
    changes.removed += fromItems.size - shared.length;

    shared.forEach((key) => {
      const moved = !kept.has(key);
      changes.moved += moved ? 1 : 0;

      if (!sideBySide) {
        return;
      }

      const fromItem = fromItems.get(key)!;
      const toItem = toItems.get(key)!;
      const a = box(fromItem);
      const b = box(toItem);
      const y1 = a.top + a.height / 2 - origin.top;
      const y2 = b.top + b.height / 2 - origin.top;
      const x1 = a.right - origin.left;
      const x2 = b.left - origin.left;
      // A short item, such as a chip, leads straight out to the edge of its
      // field, so the curve stays between the two sides rather than crossing
      // whatever else is beside the item.
      const curveStart = Math.max(x1, fromRight - origin.left);
      const bend = Math.max((x2 - curveStart) / 2, 12);

      links.push({
        path: `M ${x1} ${y1} H ${curveStart} C ${curveStart + bend} ${y1}, ${x2 - bend} ${y2}, ${x2} ${y2}`,
        ends: [
          [x1, y1],
          [x2, y2],
        ],
        items: [fromItem, toItem],
        moved,
      });
    });
  }

  return {links, changes};
}
