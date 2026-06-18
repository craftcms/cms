# 10 — `Drag` / `DragDrop` Implementation Notes

> Status: **Implemented.** Built against `09-drag-dragdrop-design.md` on top of the
> shipped `BaseDrag` (docs 07/08). All gates green in the worktree:
> `check:types`, `check:format`, `test` (**215 tests**, was 162 — +53), `build`.
> `dist/index.js` jQuery references: **0** (`grep -ciE "jquery|\$\(" dist/index.js`).

## What shipped

| File | Change |
| --- | --- |
| `src/utils/misc.ts` | **Promoted** `isPlainObject(val)` here (was module-private in `base-drag.ts`). Single shared copy; barrel-exported from `utils/index.ts`. |
| `src/drag/base-drag.ts` | Now imports `isPlainObject` from `utils/misc` (deleted its local copy). No behavior change. |
| `src/drag/drag.ts` | **NEW.** `class Drag extends BaseDrag` — helpers/clones, lag-follow loop, WAAPI return-to-source / fade. |
| `src/drag/drag-drop.ts` | **NEW.** `class DragDrop extends Drag` — drop targets + hit-detection. |
| `src/index.ts` | Named exports `Drag`, `DragSettings`, `DragDrop`, `DragDropSettings`; added `Drag`/`DragDrop` to the `Garnish` namespace object. |
| `tests/drag-drop.test.ts` | **NEW.** 53 tests covering `Drag` + `DragDrop`. |

## Exact exported public API

### `src/drag/drag.ts`

```ts
export type DragHelper =
  | ((helper: HTMLElement, index: number) => HTMLElement)
  | ElementInput
  | null;

export interface DragSettings extends BaseDragSettings {
  filter: (() => ElementInput) | string | null;
  singleHelper: boolean;
  collapseDraggees: boolean;
  removeDraggee: boolean;
  hideDraggee: boolean;
  copyDraggeeInputValuesToHelper: boolean;
  helperOpacity: number;
  moveHelperToCursor: boolean;
  helper: DragHelper;
  helperBaseZindex: number;
  helperLagBase: number;
  helperLagIncrementDividend: number;
  helperSpacingX: number;
  helperSpacingY: number;
  onReturnHelpersToDraggees: () => void;
}

export class Drag<S extends DragSettings = DragSettings> extends BaseDrag<S> {
  static override readonly defaults: DragSettings;

  // public properties
  targetItemWidth / targetItemHeight / targetItemPositionInDraggee: number | null;
  $draggee: HTMLElement[];
  otherItems: Element[];
  totalOtherItems: number | null;
  helpers: HTMLElement[];
  helperTargets / helperPositions: Array<{left: number; top: number}>;
  helperLagIncrement: number | null;
  updateHelperPosFrame: number | null;
  lastMouseX / lastMouseY: number | null;
  draggeeDisplay: string | null;
  draggeeVirtualMidpointX / draggeeVirtualMidpointY: number | null;

  constructor(items?: DragItemsInput | Partial<S> | null, settings?: Partial<S>);

  override allowDragging(): boolean;     // !_returningHelpersToDraggees
  override startDragging(): void;
  override drag(didMouseMove: boolean): void;
  override stopDragging(): void;
  override destroy(): void;

  findDraggee(): HTMLElement[];
  setDraggee(draggee: HTMLElement[]): void;
  appendDraggee(newDraggee: HTMLElement[]): void;
  getHelperTargetX(real?: boolean): number;
  getHelperTargetY(real?: boolean): number;
  returnHelpersToDraggees(): void;
  fadeOutHelpers(): void;
  onReturnHelpersToDraggees(): void;     // RAF-deferred
}
export default Drag;
```

Private: `_createHelper`, `_getHelperTarget`, `_updateHelperPos`, `_showDraggee`,
`_hide`, `_show`. Event triggered: `returnHelpersToDraggees` (+ inherited drag events).

### `src/drag/drag-drop.ts`

```ts
export interface DragDropSettings extends DragSettings {
  dropTargets: ElementInput | (() => ElementInput) | null;
  onDropTargetChange: (activeDropTarget: HTMLElement | null) => void;
  activeDropTargetClass: string;
}

export class DragDrop<S extends DragDropSettings = DragDropSettings> extends Drag<S> {
  static override readonly defaults: DragDropSettings;

  $dropTargets: HTMLElement[] | null;     // null when none
  $activeDropTarget: HTMLElement | null;  // RAW element, NOT jQuery (see deviation 4)

  constructor(settings?: Partial<S>);      // (settings) only — no positional items
  updateDropTargets(): void;
  override onDragStart(): void;
  override onDrag(): void;
  override onDragStop(): void;
}
export default DragDrop;
```

No `drop` / `onDrop` event — consumers read `$activeDropTarget` inside their own
`dragStop`/`onDragStop` handler (legacy contract preserved).

### `src/utils/misc.ts` (addition)

```ts
export function isPlainObject(val: unknown): val is Record<string, unknown>;
```

## Deviations from doc 09 (and why)

1. **`startDragging` hook ordering — inline-replication path (doc §1.5, risk 3).**
   We call `this.onBeforeDragStart()` **first**, then snapshot + build helpers +
   start the lag loop, then inline the tail of `BaseDrag.startDragging`
   (`dragging = true; setScrollContainer(); onDragStart(); globals.activateEventsMuted = true`)
   instead of calling `super.startDragging()`. This preserves the legacy order
   (onBeforeDragStart fires before helpers exist) and duplicates ~4 lines of super.
   If `BaseDrag` later grows a `protected _beginDragging()` excluding
   `onBeforeDragStart`, switch to it.

2. **Constructor merge — explicit form (doc §1.1, recommended).**
   `super(resolvedItems, {...Drag.defaults, ...callerSettings})`. Since
   `Drag.defaults` already spreads `BaseDrag.defaults`, and `BaseDrag`'s
   constructor then re-layers `BaseDrag.defaults` *under* that via `setSettings`
   (`{...base, ...defaults, ...settings}`), every key resolves correctly with
   caller overrides winning. Verified via the settings/defaults tests.
   `DragDrop`'s constructor takes `(settings)` only (legacy `init(settings)`) and
   merges `{...DragDrop.defaults, ...settings}`.

3. **Return-to-source uses WAAPI on `left`/`top` (doc §3.1).** Mirrors
   `Modal._fade`: tracked in `_returnAnimations`, cancelable, reduced-motion
   gated, and feature-detected (`typeof helper.animate !== 'function'`). I kept
   `left`/`top` interpolation (Velocity parity) rather than switching to
   `transform` — simpler and the end state commits `left`/`top` either way. The
   `transform` perf optimization remains available as a future change; **validate
   landing pixel-exactness in the playground** if switched.

4. **Completion fires on the LAST helper (doc §3.2).** `returnHelpersToDraggees`
   counts a `pending` countdown and calls `_showDraggee` when it hits 0 (or
   immediately on the no-WAAPI / reduced-motion path), rather than keying off
   helper 0's callback like legacy. Safe improvement; equivalent when durations
   match.

5. **`$activeDropTarget` is a raw `HTMLElement | null`** (doc §5.2), not a jQuery
   object. **Consumers that read `this.$activeDropTarget[0]` break** — they must
   use the element directly. `onDropTargetChange` is also called with the raw
   element (or `null`). Flag for the compat layer if any external plugin needs the
   jQuery-wrapped form.

6. **`isPlainObject` promoted to `utils/misc.ts`** (doc §6 recommendation).
   `base-drag.ts` and `drag.ts` share one copy; also barrel-exported.

7. **No `_muteActivateEvents` indirection** — `startDragging` sets
   `globals.activateEventsMuted = true` inline, identical to `BaseDrag`.

## happy-dom / testing caveats (for the next engineer)

- **`element.animate` is `undefined`** → `returnHelpersToDraggees` / `fadeOutHelpers`
  take the no-WAAPI fallback and resolve **synchronously**. That's the only path
  unit-tested; the real WAAPI tweens are playground-only. One test injects a fake
  `helper.animate` returning a never-finishing animation to assert the
  `_returningHelpersToDraggees` gate (`allowDragging()` → false mid-flight).
- **The lag-follow loop (`_updateHelperPos`) re-schedules itself every RAF.** The
  `drag-drop.test.ts` animation mock therefore pushes RAF callbacks onto a manual
  `rafQueue` (flushed explicitly via `flushRaf()`) instead of running them
  synchronously — a synchronous stub would infinitely recurse. `startDragging`
  tests stub `_updateHelperPos` to a no-op.
- **No layout:** `getOuterWidth/Height` read `offsetWidth/Height` (0 in happy-dom)
  and `getBoundingClientRect` returns zeros. Tests `vi.spyOn(...,
  'getBoundingClientRect')` where `getOffset`/`hitTest` math matters; helper
  sizing just needs the call to not throw.
- **Spying on private methods:** `vi.spyOn(d as never, '_x')` typechecks for reads
  but **not** for `.mockImplementation`. Cast through a `DragPrivate` interface
  (`d as unknown as DragPrivate`) when you need to stub.

## Playground-only (NOT unit-tested — needs real layout/pointer/animation)

- Helpers actually cloning, sizing to source (border-box parity), and lag-following
  the cursor — multi-helper spacing/lag and `singleHelper`.
- `moveHelperToCursor`, `helperOpacity`, custom `settings.helper` wrapper visuals.
- The **WAAPI return-to-source tween** landing exactly on each draggee's offset
  with no flash before removal; `fadeOutHelpers`; reduced-motion jump-home.
- `DragDrop` active-target highlighting over real laid-out targets; overlap /
  first-match; auto-scroll + drop-target interplay.
- `copyDraggeeInputValuesToHelper` preserving real input/radio/checkbox state.

## For the `DragSort` port (next engineer)

- `Drag.$draggee` / `BaseDrag.$items` are **`HTMLElement[]` / `Element[]`** — array
  ops only (`.indexOf`, `.includes`, `[i]`), never `.eq()`/`$.inArray`.
- Reserved/claimed property names now in use by `Drag` (do not collide):
  `targetItemWidth`, `targetItemHeight`, `targetItemPositionInDraggee`, `$draggee`,
  `otherItems`, `totalOtherItems`, `helpers`, `helperTargets`, `helperPositions`,
  `helperLagIncrement`, `updateHelperPosFrame`, `lastMouseX/Y`, `draggeeDisplay`,
  `draggeeVirtualMidpointX/Y`. Private: `_returningHelpersToDraggees`,
  `_returnAnimations`, `_createHelper`, `_getHelperTarget`, `_updateHelperPos`,
  `_showDraggee`, `_hide`, `_show`.
- `Drag` exposes `draggeeVirtualMidpointX/Y` (computed in `drag()` before
  `super.drag()`), `appendDraggee`, and `setDraggee` — the hooks `DragSort` builds
  insertion logic on.
- `Drag` does **not** override `onDrag` (its per-frame work is in the
  `_updateHelperPos` RAF loop), so `DragDrop.onDrag`'s `super.onDrag()` chains
  straight to `BaseDrag.onDrag` (RAF-deferred event emit). `DragSort` should
  follow the same pattern.
- Override lifecycle via real `constructor` + `super.method()` — no
  `init()`/`this.base()` trampoline.
```
