# 12 — `DragSort` Implementation Notes

> Status: **Implemented.** Built against `11-dragsort-design.md` on top of the shipped
> `Drag` (docs 09/10) / `BaseDrag` (docs 07/08). With `DragSort` landed, the **drag
> cluster is COMPLETE** — no drag modules remain. All gates green in the worktree:
> `check:types`, `check:format`, `test` (**247 tests**, was 215 — +32), `build`,
> `playground:build`. `dist/index.js` jQuery references: **0**
> (`grep -ciE "jquery|\$\(" dist/index.js`).

## What shipped

| File | Change |
| --- | --- |
| `src/drag/drag-sort.ts` | **NEW.** `class DragSort extends Drag` — sortable list with live insertion feedback, `_getClosestItem` spatial hit-test, midpoint cache. |
| `src/index.ts` | Named exports `DragSort`, `DragSortSettings`; added `DragSort` to the `Garnish` namespace object. |
| `tests/drag-sort.test.ts` | **NEW.** 32 tests covering settings, insertion, gating, closest-item math, DOM reorder, lifecycle hooks, `sortChange`, magnet, pointer wiring. |
| `playground/{index.html,main.ts,styles.css}` | **NEW** section 9 "DragSort — reorderable list" (utilities renumbered to 10). |
| `README.md`, `docs/06-api-reference.md` | DragSort marked **supported**; drag cluster **COMPLETE**; reference entry + usage example added. |

## Exact exported public API

```ts
export type DragSortInsertion =
  | ((draggee: HTMLElement[]) => HTMLElement | string)
  | HTMLElement
  | string
  | null;

export interface DragSortSettings extends DragSettings {
  container: ElementInput | null;
  insertion: DragSortInsertion;
  moveTargetItemToFront: boolean;
  magnetStrength: number;
  onInsertionPointChange: () => void;
  onSortChange: () => void;
  canInsertBefore: (item: HTMLElement) => boolean;
  canInsertAfter: (item: HTMLElement) => boolean;
}

export class DragSort<S extends DragSortSettings = DragSortSettings> extends Drag<S> {
  static override readonly defaults: DragSortSettings;

  $heightedContainer: HTMLElement | null;
  $insertion: HTMLElement | null;
  insertionVisible: boolean;
  oldDraggeeIndexes: number[] | null;
  newDraggeeIndexes: number[] | null;
  closestItem: HTMLElement | null;

  constructor(items?: DragItemsInput | Partial<S> | null, settings?: Partial<S>);

  createInsertion(): HTMLElement | null;
  canInsertBefore(item: HTMLElement): boolean;
  canInsertAfter(item: HTMLElement): boolean;
  override getHelperTargetX(real?: boolean): number;
  override getHelperTargetY(real?: boolean): number;
  override onDragStart(): void;
  override onDrag(): void;
  override onDragStop(): void;
  onInsertionPointChange(): void;   // RAF-deferred
  onSortChange(): void;             // RAF-deferred
}
export default DragSort;
```

Private: `_precalculateMidpoints`, `_getVisibleItems`, `_getItemIndex`,
`_getDraggeeIndexes`, `_getClosestItem`, `_clearMidpoints`, `_getItemMidpoint`,
`_measureMidpoint`, `_updateInsertion`, `_moveDraggeeToItem`,
`_placeInsertionWithDraggee`, `_removeInsertion`, `_sortItemsByDomOrder`,
`_siblingIndex`, `_toInsertionElement`. Events triggered: `insertionPointChange`,
`sortChange` (+ inherited `Drag`/`BaseDrag` events).

## Deviations from doc 11 (and why)

1. **Midpoint fallback dropped (doc §2.1).** Legacy `_getItemMidpoint` had a second,
   pre-optimization path that *temporarily re-inserted the draggee next to each
   candidate and measured it via `$.offset()/outerWidth()`* (the
   `$.data('midpointVersion', …)` dance). Because the modern flow **always** runs
   `_precalculateMidpoints()` at drag start, that path is dead for `$items`; it only
   ever mattered for the non-cached `insertion` placeholder. The port replaces it with
   a direct `getBoundingClientRect()` measurement (`_measureMidpoint`) for any element
   not in the cache. Faithful to the *optimized* legacy path; avoids layout thrash.
   `_midpointVersion` is kept (bumped by `_clearMidpoints`) for parity/debug, but the
   `Map` cache is the real store.

2. **Lifecycle hooks, not `startDragging`/`drag`/`stopDragging` (doc §1.4).** All
   DragSort-specific work lives in `onDragStart`/`onDrag`/`onDragStop`, exactly as
   legacy did (`this.base()` → `super.*`). The synchronous setup/build that `Drag`
   added (helper clones, lag loop) stays in `Drag.startDragging`; DragSort never
   overrides it. `super.onDragStart()`/`super.onDrag()`/`super.onDragStop()` chain
   straight to `BaseDrag.*` (the RAF event emitters), since neither `Drag` nor
   `DragDrop` overrides those — the same pattern `DragDrop` uses.

3. **`$().add($items)` → `compareDocumentPosition` re-sort (doc §4 #15).** After every
   DOM move (`_moveDraggeeToItem`) and on drop, `$items` is re-sorted into document
   order via `_sortItemsByDomOrder` so `_getDraggeeIndexes` reports the real order.
   This replaces jQuery's implicit DOM-order sorting in `.add()`.

4. **Draggee block move via `ChildNode.before/after(...spread)` (doc §4 #7–10).**
   `$draggee.insertBefore/After(item)` → `item.before(...this.$draggee)` /
   `item.after(...this.$draggee)`, which moves the whole block in order in one call.
   `moveTargetItemToFront` uses `this.$draggee[1].before(this.$draggee[0])`.

5. **`getHelperTargetX/Y` gained the `Drag` `real` parameter.** Legacy DragSort's
   override took no args; the modern `Drag` signature is `(real = false)`. When
   `magnetStrength === 1` (or no draggee yet) it delegates to `super.getHelperTargetX/Y(real)`
   (so `moveHelperToCursor` still works); otherwise it applies the magnet formula
   against `getOffset(this.$draggee[0])` (legacy used `$draggee.offset()`, i.e. the
   first element).

6. **`insertion` markup parse via `<template>`.** A string `insertion` is parsed with
   a `<template>` and returns `content.firstElementChild`. Multi-root markup keeps only
   the first element (legacy `$(markup)` kept all) — placeholders are single elements
   in practice; documented in doc 11 §7.4.

7. **`DragSort` auto-returns helpers on drop.** Unlike `Drag`/`DragDrop` (where the
   consumer calls `returnHelpersToDraggees()` in their own `dragStop`), `DragSort.onDragStop`
   calls it itself — legacy parity (the sort *is* the drop action).

## happy-dom / testing caveats (for the next engineer)

- **`element.animate` is `undefined`** → `returnHelpersToDraggees` (called inside
  `onDragStop`) takes the synchronous no-WAAPI fallback. Set `helpers = []` /
  `draggeeDisplay` in `onDragStop` tests to keep that path trivial.
- **RAF is queued, not synchronous.** As in `drag-drop.test.ts`, the animation
  module's `requestAnimationFrame` is mocked onto a manual `rafQueue`; the lag-follow
  loop would infinitely recurse under a synchronous stub. The RAF-deferred event
  emitters (`dragStart`, `sortChange`, `insertionPointChange`) only fire after an
  explicit `flushRaf()`.
- **No layout** → `getBoundingClientRect()` returns zeros. The `_getClosestItem` math
  tests `vi.spyOn(el, 'getBoundingClientRect')` to stub a vertical list, then call
  `_precalculateMidpoints()` + `_getClosestItem()` directly.
- **Spying private methods:** cast through the `DragSortPrivate` interface
  (`ds as unknown as DragSortPrivate`) to stub `_updateInsertion`/`_getClosestItem` or
  read `_allMidpoints`.

## Playground-only (NOT unit-tested — needs real layout/pointer/animation)

- Items actually shifting as the draggee passes (real reflow); the `insertion`
  placeholder showing the landing slot.
- `magnetStrength > 1` rubber-banding the helper toward the dragged item's home.
- Large-list (`> 200` items) viewport-filter perf path in `_getClosestItem`.
- `axis`-locked sorting feel; `moveTargetItemToFront`; multi-draggee blocks.
- The WAAPI return-to-source tween landing exactly on each row's offset (inherited
  from `Drag`).

See playground section 9 ("DragSort — reorderable list"): a 5-row sortable list
constrained to `container`, `axis: 'y'`, with a dashed `insertion` placeholder, logging
`dragStart`/`drag`/`dragStop`/`insertionPointChange`/`sortChange`.
