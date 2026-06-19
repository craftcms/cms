# 08 — Drag Foundation Implementation Notes (`BaseDrag`, `DragMove`, Modal wiring)

> Status: **Implemented.** Built against the `07-drag-foundation-design.md` contract.
> All four gates are green in the worktree:
> `check:types`, `check:format`, `test` (162 tests, was 121), `build`.

## What shipped

| File | Change |
| --- | --- |
| `src/utils/scroll.ts` | **NEW.** Exported, axis-aware `getScrollParent(el, axis)` + `isWindowScrollContainer(container)`. Single source of truth. |
| `src/utils/animation.ts` | Deleted its private `getScrollParent`; now imports from `scroll.ts`. |
| `src/utils/dom.ts` | Added `getOuterWidth(el)` / `getOuterHeight(el)` (reserved for the `Drag` port). |
| `src/utils/index.ts` | Re-exports `getScrollParent`, `isWindowScrollContainer`, `getOuterWidth`, `getOuterHeight`. |
| `src/drag/base-drag.ts` | **NEW.** `class BaseDrag extends Base` — the full §1 contract, Pointer Events, WeakMap registry, native auto-scroll. |
| `src/drag-move.ts` | Replaced the throwing stub with the real `class DragMove extends BaseDrag`. |
| `src/modal.ts` | Removed both `throw` blocks; wired `draggable`→`DragMove`, `resizable`→`BaseDrag`; ported `_handleResizeStart`/`_handleResize`; typed `dragger`/`resizeDragger`; added dragger teardown to `destroy()`. |
| `src/index.ts` | Exports `BaseDrag` + `BaseDragSettings` (named) and adds `BaseDrag` to the `Garnish` namespace (alongside the now-real `DragMove`). |
| `tests/drag.test.ts` | **NEW.** 36 tests. |
| `tests/modal.test.ts` | Replaced the "PoC unsupported" block with draggable/resizable wiring + resize-math tests (7 tests). |

## Exact exported public API

### `src/drag/base-drag.ts`

```ts
export type DragItemsInput = ElementInput;
export type DragHandle =
  | ElementInput
  | ((item: Element) => ElementInput)
  | null;

export interface BaseDragSettings extends GarnishBaseSettings {
  minMouseDist: number | null;
  handle: DragHandle;
  axis: 'x' | 'y' | null;            // X_AXIS | Y_AXIS | null
  ignoreHandleSelector: string | null;
  onBeforeDragStart: () => void;
  onDragStart: () => void;
  onDrag: () => void;
  onDragStop: () => void;
}

/** Read the owning dragger of an item (test/introspection helper). */
export function getItemDragger(item: Element): BaseDrag | undefined;

export class BaseDrag<S extends BaseDragSettings = BaseDragSettings> extends Base<S> {
  static readonly minMouseDist = 1;
  static readonly windowScrollTargetSize = 25;
  static readonly defaults: BaseDragSettings;

  // public properties (names + semantics per doc 07 §1.3)
  $items: Element[];
  $targetItem: HTMLElement | null;
  dragging: boolean;
  mousedownX/Y, realMouseX/Y, mouseX/Y, mouseDistX/Y, mouseOffsetX/Y: number | null;
  scrollProperty: 'scrollTop' | 'scrollLeft' | null;
  scrollAxis: 'X' | 'Y' | null;
  scrollDist: number | null;
  scrollFrame: number | null;

  constructor(items?: DragItemsInput | Partial<S> | null, settings?: Partial<S>);

  // items
  addItems(items: DragItemsInput): void;
  removeItems(items: DragItemsInput): void;
  removeAllItems(): void;
  getPrevItem(item: Element): Element | null;
  getNextItem(item: Element): Element | null;

  // lifecycle (overridable via super-dispatch)
  allowDragging(): boolean;            // default true
  startDragging(): void;
  drag(didMouseMove: boolean): void;
  stopDragging(): void;
  setScrollContainer(): void;
  isScrollingWindow(): boolean;
  override destroy(): void;

  // hooks (trigger event + settings.on*)
  onBeforeDragStart(): void;           // sync
  onDragStart(): void;                 // RAF-deferred
  onDrag(): void;                      // RAF-deferred
  onDragStop(): void;                  // RAF-deferred
}
```

Events triggered: `beforeDragStart`, `dragStart`, `drag`, `dragStop` (+ inherited `destroy`).

### `src/drag-move.ts`

```ts
export class DragMove extends BaseDrag {
  override onDrag(): void; // sets $targetItem.style.left/top, then super.onDrag()
}
export { type BaseDragSettings }; // re-exported for convenience
```

### `src/utils/scroll.ts`

```ts
export function getScrollParent(el: Element, axis?: 'x' | 'y' | null): Element | Window;
export function isWindowScrollContainer(container: Element | Window): boolean;
```

### `src/utils/dom.ts` (additions)

```ts
export function getOuterWidth(elem: HTMLElement): number;  // offsetWidth
export function getOuterHeight(elem: HTMLElement): number; // offsetHeight
```

## Modal draggable/resizable status

**Both work; both default to `false`.** No code path throws anymore.

- **`draggable`** → `new DragMove(this.$container, {handle})` where `handle` is the
  container, or `dragHandleSelector`'s resolved element. The dragged item is always
  the container; only the handle differs.
- **`resizable`** → creates a `<div class="resizehandle">` (innerHTML = `ResizeHandle`
  SVG, `touch-action: none`), appends it to the container, and drives it with a
  `BaseDrag` whose `onDragStart`/`onDrag` call the ported `_handleResizeStart` /
  `_handleResize`. RTL mirrors the horizontal direction (`globals.ltr`).
- **`destroy()`** tears down `dragger?.destroy()` + `resizeDragger?.destroy()` before
  `super.destroy()`.
- `dragger: DragMove | null`, `resizeDragger: BaseDrag | null` (were `unknown`).

## Deviations from doc 07 (and why)

1. **`getScrollParent` body order.** The doc described starting at the element's
   nearest scroll parent then checking html/body. The implementation climbs
   `parentElement` and checks **html/body first** each step (returns `win`), then
   tests `overflow` auto/scroll/overlay **and** axis-scrollability. Net behavior
   matches the legacy `setScrollContainer` walk; it is a strict superset of the old
   private `animation.ts` finder (which only checked `overflowY` + vertical
   scrollability and didn't normalize html/body), so `scrollContainerToElement`
   keeps working. The `getScrollParent(target)` single-arg form (no axis) accepts
   **either** axis — also a superset.

2. **`DragMove.onDrag` calls `super.onDrag()`** (doc §5 Option A). Legacy did not, so
   legacy `DragMove` never emitted `drag`. Ours does (strictly more capable). The CSS
   `left/top` write is synchronous; the event emission stays RAF-deferred via super.

3. **Auto-scroll clamp uses `clientHeight`/`clientWidth`** for the element-container
   case, not legacy `outerHeight()` (doc §4.2 / §9 risk 3). Window case uses
   `documentElement.scrollHeight/Width - innerHeight/Width` for `scrollMax`.
   **Validate live** in the playground.

4. **Pointer Events** replace the legacy `mousedown`+document-`mousemove`/`mouseup`
   and the entire touch-shim path (doc §3). `pointerdown` binds on the handle;
   `pointermove`/`pointerup`/`pointercancel` bind on `document`. `pointercancel` is
   treated as `pointerup` (legacy had no equivalent — fixes a possible stuck drag).
   `_pointerId` is tracked and move/up from a different pointer id are ignored
   (multi-touch safety). **`touch-action: none`** is required on handles to prevent
   the browser eating the gesture — Modal sets it on the resize handle; consumers
   must set it on their own draggable handles (the draggable-container path does not
   force it, to avoid surprising existing layouts — flagged for the playground).

5. **`_computeEdgeScroll` extracted as a private helper.** Doc §7 recommended
   factoring the "is the pointer in the edge band, how far" math into a pure-ish
   function. Done — it returns `{property, axis, dist} | null`; `drag()` consumes it.
   This is what the auto-scroll-decision tests assert (via the public `scroll*`
   fields), since the live RAF loop and window scroll aren't reproducible in
   happy-dom.

6. **`_deinitItem` removes listeners from the resolved handle**, not the item
   (legacy called `removeAllListeners(item)` but bound on the handle). With native
   delegation the listener lives on `_getItemHandle(item)`, so we remove from there.
   For the common `handle: null` case the handle *is* the item, so behavior matches;
   for a separate handle this is the correct fix.

7. **`getItemDragger(item)` exported** as a small introspection/test helper over the
   module-level `WeakMap<Element, BaseDrag>` (`dragRegistry`). Not in the doc's API
   list, but harmless and keeps the WeakMap private while letting tests assert
   ownership. The `Drag`/`DragSort` ports can use it instead of re-exposing the map.

## happy-dom / testing caveats (for the next engineer)

- **`PointerEvent` exists, but `pageX`/`pageY`/`pointerId` are always `0`** (no
  geometry). Tests construct a real `PointerEvent` and override those props with
  `Object.defineProperty` before dispatch (see `firePointer` in `tests/drag.test.ts`).
- **No layout:** `getBoundingClientRect()` returns zeros, so coordinate-math tests
  `vi.spyOn(el, 'getBoundingClientRect')` to return a known rect (drives `getOffset`).
- **RAF hooks:** `tests/drag.test.ts` `vi.mock('../src/utils/animation')` to make
  `requestAnimationFrame` run its callback synchronously, so the deferred
  `onDragStart`/`onDrag`/`onDragStop` events are assertable without a real frame.
- **`element.animate` is `undefined`** in happy-dom — irrelevant to drag, but it's
  why Modal's fade resolves synchronously in the modal tests.

## Playground-only (NOT unit-tested — needs real layout/pointer)

- End-to-end `DragMove` element following the cursor.
- Modal `draggable` (drag by handle / container) and `resizable` (symmetric grow/
  shrink; RTL mirroring) actually moving/resizing.
- The live auto-scroll RAF loop near container/window edges, including the
  window-scroll cursor correction (`mouseX/Y -= scrollPos - newScrollPos`) keeping
  the helper under the cursor (doc §9 risk 4).
- `touch-action: none` behavior and `pointercancel` cleanup on touch devices.
- Deeply-nested scroll-container `getScrollParent` edge cases (doc §9 risk 2).

> A `playground/` drag page (free `DragMove` box, draggable+resizable Modal, tall
> scroll container) was **not** added in this slice — recommended next, so the above
> can be eyeballed.

## For the `Drag` / `DragSort` port (next engineers)

- `BaseDrag.$items` is now **`Element[]`**, not a jQuery collection. Use array
  indexing (`this.$items[i]`, `.indexOf`, `.length`), not `.eq()`/`$.inArray`.
- Reserved-but-unimplemented property names `Drag` will add (do not collide):
  `targetItemWidth`, `targetItemHeight`, `otherItems`, `draggeeDisplay`, `$draggee`,
  `helpers`. `onReturnHelpersToDraggees` belongs to `Drag`, not `BaseDrag`.
- `getOuterWidth`/`getOuterHeight` (`utils/dom.ts`) are the native `.outerWidth()`/
  `.outerHeight()` replacements `Drag` reads off `$targetItem`.
- Override `startDragging`/`drag`/`stopDragging` with a real `constructor` + `super`
  and `super.method()` — there is no `init()`/`this.base()` trampoline.
- Use `getItemDragger(item)` (or import the pattern) instead of `$.data(item, 'drag')`.
```
