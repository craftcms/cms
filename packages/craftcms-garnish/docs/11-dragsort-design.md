# 11 — `DragSort` Design (sortable lists + live insertion feedback)

> **Status:** design contract. Specifies the TypeScript surface for the legacy `DragSort.js`
> (~697 LOC) port onto the **already-shipped** modern `Drag` (`src/drag/drag.ts`, docs 09/10),
> which itself sits on `BaseDrag` (docs 07/08). `DragSort` is the last Phase-2 (drag cluster)
> module — once it lands the drag cluster is COMPLETE.
>
> **Grounding:** every signature below is written against the *real* modern API, not legacy
> names. The load-bearing facts:
>
> - `BaseDrag.$items` is `Element[]`, `$targetItem` is `HTMLElement | null`; `Drag.$draggee` is
>   `HTMLElement[]` (target always index 0). Array ops only — `.indexOf`/`.includes`/`[i]`,
>   never `.eq()`/`$.inArray`.
> - `BaseDrag.getPrevItem(item)`/`getNextItem(item)` take an `Element` and return
>   `Element | null` (no jQuery).
> - `Drag` computes `draggeeVirtualMidpointX/Y` inside `drag()` *before* `super.drag()` runs
>   `onDrag()`, so `DragSort.onDrag` reads fresh virtual-midpoint coords.
> - The RAF-emitting hooks `onDragStart`/`onDrag`/`onDragStop` run **synchronously** inside
>   `startDragging`/`drag`/`stopDragging`; only their `trigger(...)` emission is RAF-deferred.
>   So a `DragSort` override does its synchronous work, then calls `super.onDragStart()` (etc.)
>   to schedule the event — exactly mirroring the legacy `this.base()` order.
> - `Drag` does **not** override `onDrag`/`onDragStart`/`onDragStop`, so `super.*` from
>   `DragSort` chains straight to `BaseDrag.*` (the RAF emitters). Same pattern `DragDrop` uses.
> - `Drag.setDraggee` records `targetItemPositionInDraggee` (the target's index in the
>   *input* draggee order) and forces `$draggee[0] === $targetItem`.

---

## 0. What `DragSort` is

`DragSort extends Drag`. It is the sortable-list dragger: drag items to reorder them within their
container, with **live insertion feedback** — as the cursor moves, the dragged item (and an
optional placeholder element) is physically re-inserted into the DOM at the spot it would land,
so the surrounding items shift in real time. On drop the new order is committed and a
`sortChange` event fires if anything actually moved.

Unlike `DragDrop` (which highlights a separate drop *zone*), `DragSort` reorders the items
*amongst themselves*: the draggee moves through the list and the list reflows around it.

---

## 1. Public contract

### 1.1 Constructor

Same `(items?, settings?)` shape + plain-object param-shift as `Drag`. Mirror the `Drag`/`DragDrop`
explicit-merge form: layer `DragSort.defaults` under the caller's overrides, then delegate to
`super` (which layers `Drag.defaults`/`BaseDrag.defaults` under that — every key resolves).

```ts
constructor(items?: DragItemsInput | Partial<S> | null, settings?: Partial<S>) {
  let resolvedItems = (items as DragItemsInput) ?? null;
  if (settings === undefined && isPlainObject(items)) {
    settings = items as Partial<S>;
    resolvedItems = null;
  }
  super(resolvedItems, {...(DragSort.defaults as Partial<S>), ...(settings ?? {})});
}
```

### 1.2 Settings (`DragSortSettings`, extends `DragSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `container` | `null` | The list container (element / selector / list). At drag start, walked up to the nearest ancestor that actually has a height; the drag only sorts while the cursor is over that `$heightedContainer`. |
| `insertion` | `null` | The placeholder. A function `(draggee) => element \| markup`, an element, or an HTML string. Re-inserted just before the draggee to show the landing spot. `null` → no placeholder (the draggee itself is the feedback). |
| `moveTargetItemToFront` | `false` | If the target item isn't the first draggee, move it to the front of the draggee block in the DOM at drag start (and leave it there on drop). |
| `magnetStrength` | `1` | Divides the helper's pull toward the cursor. `1` → no magnetism (helper tracks the cursor exactly, as in `Drag`). `>1` → the helper lags toward the draggee's home, giving a rubber-band feel. |
| `onInsertionPointChange` | no-op | Fired (RAF-deferred) whenever the insertion point moves. |
| `onSortChange` | no-op | Fired (RAF-deferred) on drop when the order actually changed. |
| `canInsertBefore` | `() => true` | Gate: may the draggee be inserted *before* this item? |
| `canInsertAfter` | `() => true` | Gate: may the draggee be inserted *after* this item? |

```ts
export interface DragSortSettings extends DragSettings {
  container: ElementInput | null;
  insertion:
    | ((draggee: HTMLElement[]) => HTMLElement | string)
    | HTMLElement
    | string
    | null;
  moveTargetItemToFront: boolean;
  magnetStrength: number;
  onInsertionPointChange: () => void;
  onSortChange: () => void;
  canInsertBefore: (item: HTMLElement) => boolean;
  canInsertAfter: (item: HTMLElement) => boolean;
}
```

### 1.3 Public properties (jQuery → native)

Legacy held jQuery collections; modern keeps the `$`-prefixed names for consumer parity but
holds native types.

```ts
$heightedContainer: HTMLElement | null = null;  // legacy: jQuery
$insertion: HTMLElement | null = null;           // legacy: jQuery
insertionVisible = false;
oldDraggeeIndexes: number[] | null = null;
newDraggeeIndexes: number[] | null = null;
closestItem: HTMLElement | null = null;          // legacy: raw element already
```

Private: `_allMidpoints: Map<Element, Midpoint> | null`, `_midpointVersion: number`.

### 1.4 Methods & hooks (mapped onto the real super-dispatch)

```ts
// --- Lifecycle hooks (synchronous work, then super to RAF-emit) -----------
override onDragStart(): void;   // record indexes, moveTargetItemToFront, create+place
                                // insertion, clear+precalc midpoints, super.onDragStart()
override onDrag(): void;        // container hit-test → closest-item detection →
                                // _updateInsertion, then super.onDrag()
override onDragStop(): void;    // remove insertion, restore target pos, return helpers,
                                // super.onDragStop(), re-sort $items, fire sortChange

// --- Helper geometry override (magnetStrength) ----------------------------
override getHelperTargetX(real?: boolean): number;
override getHelperTargetY(real?: boolean): number;

// --- Insertion / gating ---------------------------------------------------
createInsertion(): HTMLElement | null;
canInsertBefore(item: HTMLElement): boolean;
canInsertAfter(item: HTMLElement): boolean;

// --- Event hooks ----------------------------------------------------------
onInsertionPointChange(): void; // RAF-deferred trigger('insertionPointChange') + callback
onSortChange(): void;           // RAF-deferred trigger('sortChange') + callback
```

Private: `_precalculateMidpoints`, `_getVisibleItems`, `_getItemIndex`, `_getDraggeeIndexes`,
`_getClosestItem`, `_clearMidpoints`, `_getItemMidpoint`, `_updateInsertion`, `_moveDraggeeToItem`,
`_placeInsertionWithDraggee`, `_removeInsertion`, `_sortItemsByDomOrder`, `_siblingIndex`,
`_toInsertionElement`.

**Events:** `insertionPointChange`, `sortChange`, plus all inherited `Drag`/`BaseDrag` events
(`returnHelpersToDraggees`, `beforeDragStart`, `dragStart`, `drag`, `dragStop`, `destroy`).

---

## 2. The `_getClosestItem` spatial hit-test (the hard part)

For each pointer frame `DragSort` must find which item the dragged element is closest to, so it
can decide where to re-insert. The legacy algorithm:

1. **Virtual midpoint.** `Drag.draggeeVirtualMidpointX/Y` is the center the *draggee would have*
   at the current cursor position (`mouse - mouseOffset + targetItem{W,H}/2`). All distances are
   measured from it.
2. **Seed.** Start by testing the draggee itself (or, when `removeDraggee`, the visible insertion)
   so the current position is the baseline closest.
3. **Walk outward both directions.** From the item *before* the first draggee, walking backward,
   then from the item *after* the last draggee, walking forward. For each candidate, compute the
   axis-appropriate distance (`X_AXIS` → dx only, `Y_AXIS` → dy only, else Euclidean).
4. **Early-skip when receding.** While walking, if a candidate is getting *further* on every
   relevant axis than the last tested one, skip testing it (but keep walking) — the cheap
   monotonic-distance filter that lets large lists bail out of full comparisons.
5. **Gate.** Only items where `canInsertBefore(item) || canInsertAfter(item)` are tested.
6. **Result.** The nearest tested item, unless it's the draggee/insertion itself → `null`.

### 2.1 Midpoint caching (perf on large lists)

Measuring `getBoundingClientRect()` per item per frame thrashes layout. So:

- **`_precalculateMidpoints()`** runs once at drag start: a single read pass over `$items` storing
  `{x, y, width, height, top, bottom}` (page coords, scroll-adjusted) in `_allMidpoints: Map`.
  `_getItemMidpoint(item)` then returns the cached value — O(1), zero layout reads mid-drag.
- **`_updateInsertion()`** moves the draggee in the DOM, which shifts neighbors, so it recomputes
  **only** the moved item + its previous/next neighbors' midpoints (not the whole list).
- **Viewport filter.** For lists `> 200` items, `_getVisibleItems()` restricts the closest-item
  scan to items within ~300px of the viewport (using the cached `top/bottom`), skipping the
  directional walk entirely.

> **Deviation from legacy (documented in doc 12).** Legacy `_getItemMidpoint` had a second,
> pre-optimization fallback that *temporarily re-inserted the draggee next to each candidate and
> measured it via `$.offset()/outerWidth()`* (the `$.data('midpointVersion', …)` path). Since the
> modern flow **always** populates `_allMidpoints` at drag start, that fallback is dead for
> `$items`; it only ever mattered for the non-cached `insertion` element. The port replaces the
> repositioning dance with a direct `getBoundingClientRect()` measurement for any element not in
> the cache. This is faithful to the *optimized* legacy path, avoids layout thrash, and is the
> behavior the midpoint cache was written to supersede. `_clearMidpoints()` invalidates the cache
> (nulls the map + bumps `_midpointVersion`); `_precalculateMidpoints()` rebuilds it.

### 2.2 Concrete shape

```ts
interface Midpoint {
  x: number; y: number; width: number; height: number; top: number; bottom: number;
}

_getClosestItem(): HTMLElement | null {
  let closest: HTMLElement | null = null;
  let closestDist = 0;
  const axis = this.settings!.axis;
  const vx = this.draggeeVirtualMidpointX!;
  const vy = this.draggeeVirtualMidpointY!;

  const test = (item: HTMLElement): void => {
    const mp = this._getItemMidpoint(item);
    const dx = Math.abs(mp.x - vx);
    const dy = Math.abs(mp.y - vy);
    const dist =
      axis === X_AXIS ? dx : axis === Y_AXIS ? dy : Math.sqrt(dx ** 2 + dy ** 2);
    if (closest === null || dist < closestDist) {
      closest = item;
      closestDist = dist;
    }
  };

  const visibleItems =
    this._allMidpoints && this.$items.length > 200 ? this._getVisibleItems() : null;

  // Seed with the draggee (or the visible insertion when removeDraggee).
  if (!this.settings!.removeDraggee) test(this.$draggee[0]!);
  else if (this.insertionVisible && this.$insertion) test(this.$insertion);

  // Baseline distances off the seed.
  let lastX: number | null = null, lastY: number | null = null;
  let startX: number | null = null, startY: number | null = null;
  if (closest) {
    const mp = this._getItemMidpoint(closest);
    if (axis !== Y_AXIS) startX = lastX = Math.abs(mp.x - vx);
    if (axis !== X_AXIS) startY = lastY = Math.abs(mp.y - vy);
  }

  const walk = (next: (i: Element) => Element | null, from: Element | null): void => {
    let other = from as HTMLElement | null;
    while (other) {
      const mp = this._getItemMidpoint(other);
      const dx = axis !== Y_AXIS ? Math.abs(mp.x - vx) : 0;
      const dy = axis !== X_AXIS ? Math.abs(mp.y - vy) : 0;
      const receding =
        (axis === Y_AXIS || (lastX !== null && dx > lastX)) &&
        (axis === X_AXIS || (lastY !== null && dy > lastY));
      if (!receding) {
        if (axis !== Y_AXIS) lastX = dx;
        if (axis !== X_AXIS) lastY = dy;
        if (this.canInsertBefore(other) || this.canInsertAfter(other)) test(other);
      }
      other = next(other) as HTMLElement | null;
    }
  };

  if (visibleItems) {
    for (const item of visibleItems) {
      if (this.canInsertBefore(item) || this.canInsertAfter(item)) test(item);
    }
  } else {
    walk((i) => this.getPrevItem(i), this.getPrevItem(this.$draggee[0]!));
    lastX = startX; lastY = startY;             // reset for the forward pass
    walk((i) => this.getNextItem(i), this.getNextItem(this.$draggee.at(-1)!));
  }

  // Ignore a "closest" that is the draggee/insertion itself.
  if (closest !== this.$draggee[0] && (!this.insertionVisible || closest !== this.$insertion)) {
    return closest;
  }
  return null;
}
```

---

## 3. Insertion / placeholder feedback

The visual feedback is the **real DOM reorder**: as `closestItem` changes, `_moveDraggeeToItem`
re-inserts the whole `$draggee` block before/after the target, and the optional `$insertion`
placeholder is re-positioned just before the draggee.

```ts
// onDrag: detect a new closest item, then update.
override onDrag(): void {
  if (this.$heightedContainer && !hitTest(this.mouseX!, this.mouseY!, this.$heightedContainer)) {
    if (this.closestItem) { this.closestItem = null; this._removeInsertion(); }
  } else {
    const prev = this.closestItem;
    this.closestItem = this._getClosestItem();
    if (prev !== this.closestItem && this.closestItem !== null) this._updateInsertion();
  }
  super.onDrag();
}

_moveDraggeeToItem(item: HTMLElement): void {
  const first = this.$draggee[0]!;
  const sameParent = first.parentNode === item.parentNode;
  const goingDown =
    this.canInsertAfter(item) && sameParent &&
    this._siblingIndex(first) < this._siblingIndex(item);
  if (goingDown) item.after(...this.$draggee);   // jQuery $draggee.insertAfter(item)
  else item.before(...this.$draggee);            // jQuery $draggee.insertBefore(item)
  this._placeInsertionWithDraggee();
  this.$items = this._sortItemsByDomOrder(this.$items);   // jQuery $().add($items) re-sort
}

_placeInsertionWithDraggee(): void {
  if (this.$insertion && this.$draggee[0]) {
    this.$draggee[0].before(this.$insertion);   // jQuery $insertion.insertBefore($draggee.first())
    this.insertionVisible = true;
  }
}
```

`_updateInsertion()` calls `_moveDraggeeToItem(this.closestItem)`, recomputes the moved item's +
neighbors' cached midpoints, then `onInsertionPointChange()`.

On drop (`onDragStop`): remove the insertion, optionally restore the target's position within the
draggee block (unless `moveTargetItemToFront`), return the helpers home (`Drag`'s
`returnHelpersToDraggees` — `DragSort` auto-returns, matching legacy), then re-sort `$items` to DOM
order, recompute `newDraggeeIndexes`, and fire `sortChange` if they differ from `oldDraggeeIndexes`.

---

## 4. jQuery removals — `DragSort.js`

| # | Legacy | Native replacement |
| --- | --- | --- |
| 1 | `$.extend({}, DragSort.defaults, settings)` | explicit `{...DragSort.defaults, ...settings}` via `super` |
| 2 | `$.isPlainObject(items)` param-shift | `isPlainObject()` (`utils/misc`) |
| 3 | `$(this.settings.insertion)` / `$(fn($draggee))` | `_toInsertionElement(...)` — `<template>` parse for markup, else element |
| 4 | `this.$draggee.offset().left/top` (magnet) | `getOffset(this.$draggee[0])` (`utils/dom`) |
| 5 | `Garnish.hitTest(x, y, $container)` | `hitTest(this.mouseX!, this.mouseY!, container)` (`utils/dom`) |
| 6 | `$(container).height()` + `.parent()` walk | `getOuterHeight(el)` + `el.parentElement` walk |
| 7 | `$draggee.first().insertBefore($draggee[1])` | `this.$draggee[1].before(this.$draggee[0])` |
| 8 | `$targetItem.insertAfter($draggee.eq(pos))` | `this.$draggee[pos].after(this.$targetItem)` |
| 9 | `$draggee.insertAfter(item)` / `.insertBefore(item)` | `item.after(...$draggee)` / `item.before(...$draggee)` |
| 10 | `$insertion.insertBefore($draggee.first())` | `this.$draggee[0].before(this.$insertion)` |
| 11 | `$insertion.remove()` | `this.$insertion.remove()` (native `ChildNode.remove`) |
| 12 | `this.$items.index(item)` | `this.$items.indexOf(item)` |
| 13 | `$draggee.index()` / `$(item).index()` | `_siblingIndex(el)` (index among element siblings) |
| 14 | `this.$draggee.eq(i)` | `this.$draggee[i]` |
| 15 | `$().add(this.$items)` (DOM-order re-sort) | `_sortItemsByDomOrder` via `compareDocumentPosition` |
| 16 | `$.contains($draggee[0], item)` | `this.$draggee[0].contains(item)` (`Node.contains`) |
| 17 | `$.data(item, 'midpoint'/'midpointVersion')` | `_allMidpoints: WeakMap`-style `Map<Element, Midpoint>` |
| 18 | `item.getBoundingClientRect()` + `pageXOffset` | same, via `win.scrollX/scrollY` |
| 19 | `Garnish.requestAnimationFrame` | `requestAnimationFrame` (`utils/animation`) |
| 20 | `$.noop` | `() => {}` |
| 21 | `$draggee.parent()[0] === $(item).parent()[0]` | `first.parentNode === item.parentNode` |

---

## 5. File layout & exports

```
src/drag/
  base-drag.ts   # EXISTS
  drag.ts        # EXISTS
  drag-drop.ts   # EXISTS
  drag-sort.ts   # NEW — class DragSort extends Drag + DragSortSettings
```

- `src/drag/drag-sort.ts` exports `DragSort` (default + named) and `DragSortSettings`.
- `src/index.ts`: named export `DragSort` + `DragSortSettings`; add `DragSort` to the `Garnish`
  namespace object so `new Garnish.DragSort(...)` resolves (compat-layer relies on it).
- **No new utils** — everything (`getOffset`, `getOuterHeight`, `hitTest`, `coerceElements`,
  `requestAnimationFrame`, `getPrevItem`/`getNextItem`, `isPlainObject`) already exists.

---

## 6. Testing strategy (happy-dom — no layout, no real pointer)

happy-dom returns `0` geometry and `element.animate` is `undefined`, so `returnHelpersToDraggees`
takes the synchronous no-WAAPI path (as in the `Drag` tests). Mirror `drag-drop.test.ts`: mock the
animation module's RAF onto a manual queue, mock `getBoundingClientRect` where midpoint math
matters.

**Unit-testable (Vitest):**

- **Settings/defaults** — `DragSort.defaults`; param-shift; `canInsertBefore/After` default `true`;
  `Drag`/`BaseDrag` keys survive the merge.
- **`createInsertion`** — function form (gets `$draggee`), markup string (parsed), element,
  and `null`.
- **`canInsertBefore`/`canInsertAfter`** — delegate to settings.
- **`_getItemIndex` / `_getDraggeeIndexes`** — indices into `$items`.
- **`_getClosestItem`** — build a vertical list, mock each item's rect, `_precalculateMidpoints`,
  set `draggeeVirtualMidpointX/Y`, assert the nearest insertable item is returned (and `null` when
  the draggee is closest); assert `canInsertBefore/After` gating excludes items.
- **`_moveDraggeeToItem` / `_placeInsertionWithDraggee` / `_removeInsertion`** — DOM reorder
  (before/after by sibling index), insertion placement + `insertionVisible` toggle.
- **`onDragStart`** — records `oldDraggeeIndexes`, creates+places insertion, precalcs midpoints,
  honors `moveTargetItemToFront` DOM reorder.
- **`onDrag`** — a changed closest item invokes `_updateInsertion` (spy); the container-miss path
  clears `closestItem` + removes the insertion.
- **`onDragStop`** — removes insertion, calls `returnHelpersToDraggees`, fires `sortChange`
  (RAF-flushed) only when indexes changed; no `sortChange` when order is unchanged.
- **`insertionPointChange` / `sortChange` events** — wired through `trigger` + the settings
  callbacks (RAF-flushed).
- **Event wiring** — synthetic `PointerEvent` (shimmed page coords like the BaseDrag tests):
  pointerdown on an item + a past-threshold pointermove starts a drag and emits `dragStart`.

**Playground-only (needs real layout/pointer/animation):**

- Items actually shifting as the draggee passes (real reflow); the `insertion` placeholder
  showing the landing slot; `magnetStrength` rubber-banding; large-list viewport-filter perf;
  `axis`-locked sorting; `moveTargetItemToFront`; multi-draggee blocks.

---

## 7. Risks

1. **`_getClosestItem` fidelity** — the receding-distance early-skip + axis branching is subtle;
   covered by focused midpoint-math tests. **Risk: medium.**
2. **DOM-order `$items` re-sort** — legacy leaned on jQuery `.add()` sorting; the
   `compareDocumentPosition` replacement must keep `$items` in document order after every move so
   `_getDraggeeIndexes` is correct. **Risk: medium** — tested directly.
3. **Midpoint-cache staleness** — `_updateInsertion` recomputes only the moved item + neighbors;
   if the move shifts more than the neighbors the cache can drift. Matches legacy's same
   optimization. **Risk: low–medium** — validate visually on large lists.
4. **`insertion` markup parsing** — `<template>`-based parse returns the first element child;
   multi-root markup drops siblings (legacy `$(markup)` kept all). **Risk: low** — placeholders
   are single elements in practice; documented.
