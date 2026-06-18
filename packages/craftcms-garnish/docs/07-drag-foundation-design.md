# 07 — Drag Foundation Design (`BaseDrag`, `DragMove`, auto-scroll)

> **Status:** design contract only. This document specifies the TypeScript surface
> the implementation engineer should build. No implementation code is written here.
>
> **Scope:** port the legacy `BaseDrag.js` (~580 LOC) and the trivial `DragMove.js`
> subclass to the modern, jQuery-free core, and re-wire `Modal`'s `draggable` /
> `resizable` options so they stop throwing. The design also reserves the
> subclass-override surface that `Drag` and `DragSort` will need later, so we do
> not paint ourselves into a corner.

---

## 0. Reuse inventory — what already exists in the modern core

Before designing anything new, here is what the modern core already provides that
`BaseDrag` should reuse (do **not** duplicate these):

| Need | Already exists | Location |
| --- | --- | --- |
| `Base` lifecycle / settings / events / DOM listeners | `Base` | `src/base.ts` |
| Namespaced DOM listener add/remove | `addListener` / `removeListener` / `removeAllListeners` | `src/base.ts` → `src/dom-listeners.ts` |
| Object + class pub/sub (`trigger`) | `EventEmitter` / `ClassEventBus` | `src/events.ts` |
| `requestAnimationFrame` / `cancelAnimationFrame` | re-exported native | `src/utils/animation.ts` |
| Euclidean distance | `getDist(x1,y1,x2,y2)` | `src/utils/misc.ts` |
| Primary-click test | `isPrimaryClick(ev)` | `src/utils/env.ts` |
| Page-relative offset | `getOffset(elem): {top,left}` | `src/utils/dom.ts` |
| Element coercion (`$(x)` → `EventTarget[]`) | `coerceElements` | `src/utils/dom.ts` |
| `window` / `document` / `body` refs | `win` / `doc` / `bod` | `src/globals.ts` |
| Mutable feature flags (`activateEventsMuted`, `rtl`/`ltr`) | `globals` | `src/globals.ts` |
| Axis constants | `X_AXIS` / `Y_AXIS` | `src/constants.ts` |
| `ResizeHandle` SVG markup | `ResizeHandle` | `src/icons/resize-handle.ts` |

**Gaps that must be filled (new code):**

1. **A scroll-parent finder.** `animation.ts` has a *private* `getScrollParent(el)`
   but it (a) is not exported, (b) only checks `overflow-y`, and (c) does not
   replicate `BaseDrag.setScrollContainer`'s axis-aware walk. The drag foundation
   needs a *shared, exported, axis-aware* finder. **Decision:** create
   `src/utils/scroll.ts`, move/generalize the finder there, and have
   `animation.ts` import it (so there is one implementation). See §4.
2. **`outerWidth` / `outerHeight` helpers.** `Drag` (downstream) reads
   `this.$targetItem.outerWidth()`. There is no native helper yet. Add
   `getOuterWidth(el)` / `getOuterHeight(el)` to `src/utils/dom.ts` returning
   `offsetWidth` / `offsetHeight` (border-box). `BaseDrag` itself does not need
   these, but reserving them now keeps the `Drag` port clean.
3. **A per-element drag registry** to replace `$.data(item, 'drag')` — a
   module-level `WeakMap<Element, BaseDrag>` (see §2).

---

## 1. `BaseDrag` public contract

### 1.1 Constructor

Legacy signature: `init(items, settings)` with a param-shift — if `settings` is
omitted and `items` is a plain object, `items` is treated as `settings`.

Modern signature (mirrors how `Modal` already does its param-shift):

```ts
export class BaseDrag<S extends BaseDragSettings = BaseDragSettings> extends Base<S> {
  constructor(
    items?: DragItemsInput | Partial<S> | null,
    settings?: Partial<S>
  ) {
    super();
    // param-shift: new BaseDrag(settings) when first arg is a plain object
    this.setSettings(resolvedSettings, BaseDrag.defaults);
    this.items = [];
    if (resolvedItems) this.addItems(resolvedItems);
  }
}
```

Where:

```ts
/** Anything addItems/removeItems accepts: a selector, element, or list. */
export type DragItemsInput = ElementInput; // reuse the core ElementInput union
```

> **Subclass note:** `Drag`/`DragSort` override `startDragging`/`drag`/`stopDragging`
> and call `super.startDragging()` etc. Because the modern `Base` uses native
> `class extends` (no `init()` trampoline, no `this.base()`), these subclasses use a
> real `constructor` + `super(...)` and `super.method()`. The compat layer is what
> bridges any legacy `extend()/init()/base()` callers — `BaseDrag` itself stays
> native.

### 1.2 Settings (every setting + default)

```ts
export interface BaseDragSettings extends GarnishBaseSettings {
  /** Min px the pointer must travel before a drag starts. `null` → static default (1). */
  minMouseDist: number | null;
  /**
   * The drag handle. Element(s), a selector resolved within each item, or a
   * function `(item) => handleElement`. `null` → the item itself is the handle.
   */
  handle: ElementInput | ((item: Element) => ElementInput) | null;
  /** Restrict movement to one axis. `X_AXIS`, `Y_AXIS`, or `null` (both). */
  axis: typeof X_AXIS | typeof Y_AXIS | null;
  /** Pointer-down on a descendant matching this selector is ignored (unless it IS the handle). */
  ignoreHandleSelector: string | null;

  onBeforeDragStart: () => void;
  onDragStart: () => void;
  onDrag: () => void;
  onDragStop: () => void;
}

static readonly defaults: BaseDragSettings = {
  minMouseDist: null,
  handle: null,
  axis: null,
  ignoreHandleSelector: 'input, textarea, button, select, .btn',
  onBeforeDragStart: () => {},
  onDragStart: () => {},
  onDrag: () => {},
  onDragStop: () => {},
};
```

Static tunables (legacy class-statics):

```ts
static readonly minMouseDist = 1;            // fallback when settings.minMouseDist === null
static readonly windowScrollTargetSize = 25; // px edge band that triggers auto-scroll
```

### 1.3 Public properties consumers/subclasses read

These are the API `Drag`/`DragSort`/`DragMove`/`Modal` depend on — keep names and
semantics exact. **Important rename:** legacy exposes `$items` / `$targetItem` as
jQuery collections. Modern uses native element arrays/elements but keeps the
`$`-prefixed names for consumer parity (same convention `Modal` already uses with
`$container` etc.).

```ts
// Items + current target
$items: Element[] = [];                 // legacy this.$items (jQuery → array)
$targetItem: HTMLElement | null = null; // the element currently being dragged (or null)

// State
dragging = false;

// Pointer coordinates (page coords)
mousedownX: number | null = null;
mousedownY: number | null = null;
realMouseX: number | null = null; // unconstrained, before axis lock
realMouseY: number | null = null;
mouseX: number | null = null;     // axis-constrained; what subclasses use
mouseY: number | null = null;
mouseDistX: number | null = null; // mouseX - mousedownX
mouseDistY: number | null = null;
mouseOffsetX: number | null = null; // pointerdown page X minus target's left offset
mouseOffsetY: number | null = null;

// Scroll-loop state (set by drag(), read by the scroll frame)
scrollProperty: 'scrollTop' | 'scrollLeft' | null = null;
scrollAxis: 'X' | 'Y' | null = null;
scrollDist: number | null = null;
scrollFrame: number | null = null;  // RAF handle
```

> `Modal._handleResize` reads `this.resizeDragger.mouseDistX` / `.mouseDistY` — so
> `mouseDistX/Y` must be populated **before** `onDrag` fires (it already is in the
> legacy flow: `_handleMouseMove` sets them, then calls `drag()` → `onDrag()`).

> **Reserved for `Drag` (not implemented in `BaseDrag`, but the property names must
> not collide):** `targetItemWidth`, `targetItemHeight`, `otherItems`,
> `draggeeDisplay`, `$draggee`, `helpers`. `BaseDrag` should leave these to
> subclasses. Document this so the `Drag` port can declare them.

### 1.4 Public methods

```ts
// Item management
addItems(items: DragItemsInput): void;
removeItems(items: DragItemsInput): void;
removeAllItems(): void;
getPrevItem(item: Element): Element | null;
getNextItem(item: Element): Element | null;

// Drag lifecycle (overridable by Drag/DragSort via super-dispatch)
allowDragging(): boolean;     // default true; subclasses gate dragging
startDragging(): void;
drag(didMouseMove: boolean): void;
stopDragging(): void;

// Scroll-container resolution
setScrollContainer(): void;
isScrollingWindow(): boolean;

// Lifecycle
override destroy(): void;      // removeAllItems() + super.destroy()
```

### 1.5 Overridable hooks (event emitters)

Each hook **triggers an object event AND calls the `settings.on*` callback**.
`onDragStart`/`onDrag`/`onDragStop` defer their body inside a single
`requestAnimationFrame` (legacy parity — coalesces rapid mousemoves);
`onBeforeDragStart` fires synchronously.

```ts
onBeforeDragStart(): void; // sync:  trigger('beforeDragStart'); settings.onBeforeDragStart()
onDragStart(): void;       // RAF:   trigger('dragStart');       settings.onDragStart()
onDrag(): void;            // RAF:   trigger('drag');            settings.onDrag()
onDragStop(): void;        // RAF:   trigger('dragStop');        settings.onDragStop()
```

> `onReturnHelpersToDraggees` is **not** part of `BaseDrag` — it lives in `Drag`.
> Listed in the brief for completeness; do not add it here.

### 1.6 Events triggered

`beforeDragStart`, `dragStart`, `drag`, `dragStop`, plus the inherited
`destroy` (from `Base`). Subscribe via `instance.on('drag', ...)` or
`Garnish.on(BaseDrag, 'drag', ...)`.

---

## 2. jQuery removals — every usage → native replacement

| # | Legacy (BaseDrag.js) | Native replacement |
| --- | --- | --- |
| 1 | `$.extend({}, defaults, settings)` (init) | `this.setSettings(settings, BaseDrag.defaults)` (already on `Base`) |
| 2 | `$.isPlainObject(items)` param-shift | local `isPlainObject()` (same helper `modal.ts` uses; factor into `utils` if not already) |
| 3 | `this.$items = $()` + `.add()` | `this.$items: Element[]`, `push` / dedupe |
| 4 | `this.$targetItem.scrollParent()` (×2: `setScrollContainer`) | new `getScrollParent(el, axis)` in `src/utils/scroll.ts` (§4) |
| 5 | `Garnish.$doc[0]` / `Garnish.$bod[0]` / `Garnish.$win[0]` comparisons | compare against `doc.documentElement` / `bod` / `win` from `globals` |
| 6 | `this._.$scrollContainer.offset().top/.left` | `getOffset(container)` (`utils/dom.ts`) — but see §3/§4 note on `getBoundingClientRect` for fixed containers |
| 7 | `.outerHeight()` / `.outerWidth()` on scroll container | `el.clientHeight` for the *inner* edge math; `el.scrollHeight`/`scrollWidth` for max. (Legacy used `outerHeight`; for the scroll container the correct value is `clientHeight`/`clientWidth` — call this out as a behavior fix, see §4 risks.) |
| 8 | `Garnish.$win.scrollTop()/scrollLeft()/height()/width()` | `win.scrollY` / `win.scrollX` / `win.innerHeight` / `win.innerWidth` |
| 9 | `this.$targetItem.offset()` (pointer-down offset) | `getOffset(this.$targetItem)` (`utils/dom.ts`) |
| 10 | `$.makeArray(items)` (addItems/removeItems) | `coerceElements(items)` (`utils/dom.ts`) → `Element[]` |
| 11 | `$.data(item, 'drag')` / `$.data(item, 'drag', this)` / `$.removeData(item, 'drag')` | module-level `const dragRegistry = new WeakMap<Element, BaseDrag>()` (mirrors `modal.ts`'s `containerModals`) |
| 12 | `$.inArray(item, this.$items)` | `this.$items.indexOf(item)` |
| 13 | `this.$items.splice(index, 1)` | unchanged (already an array op once `$items` is native) |
| 14 | `this.$items.eq(index ± 1)` (getPrev/getNextItem) | `this.$items[index ± 1] ?? null` |
| 15 | `item instanceof jQuery` (getPrev/getNext) | drop — modern API takes a raw `Element`. (Compat layer can unwrap jQuery if needed.) |
| 16 | `$(item)` handle resolution + `$(selector, item)` + `:first` munging | resolve via `coerceElements` / `item.querySelector(selector)`; see §2.1 |
| 17 | `Craft.ensureEndsWith(s, ':first')` in `_getItemHandle` | drop the `:first` hack — `querySelector` already returns the first match. See §2.1. |
| 18 | `$(ev.target)` + `.is(sel)` / `.closest(sel).length` (ignoreHandleSelector) | `(ev.target as Element).closest(selector)` (covers both `.is` and `.closest`) |
| 19 | `this.addListener(handle, 'mousedown', fn)` | `this.addListener(handle, 'mousedown', fn)` — same `Base.addListener`, but see §3 (pointer events) |
| 20 | `this.addListener(Garnish.$doc, 'mousemove'/'mouseup', '_handle…')` | `this.addListener(doc, 'pointermove'/'pointerup', '_handle…')` (§3) |
| 21 | `this.removeAllListeners(Garnish.$doc)` on mouseup | `this.removeAllListeners(doc)` |
| 22 | `Garnish.requestAnimationFrame` / `cancelAnimationFrame` | import from `utils/animation.ts` |
| 23 | `Garnish.activateEventsMuted = true/false` | `globals.activateEventsMuted = true/false` |
| 24 | `$.noop` (default callbacks) | `() => {}` |
| 25 | auto-scroll `setInterval`/RAF loop touching `Garnish.$win[prop]()` | `utils/scroll.ts` scroll helpers (§4); the loop itself stays RAF-based |

### 2.1 Handle resolution (`_getItemHandle`)

```ts
private _getItemHandle(item: Element): Element[] {
  const {handle} = this.settings!;
  if (!handle) return [item];

  if (typeof handle === 'function') return coerceElements(handle(item));

  if (typeof handle === 'string') {
    // Legacy split commas and forced `:first` per selector to dodge
    // craftcms/cms#12896. Native querySelector already returns the first match,
    // so resolve each comma-part with item.querySelector and collect non-null.
    return handle
      .split(',')
      .map((s) => item.querySelector(s.trim()))
      .filter((el): el is Element => el !== null);
  }

  // Element / list of elements passed directly (Modal passes the container).
  return coerceElements(handle);
}
```

> The legacy `:first` munging existed only because jQuery selectors can return
> multiple nodes; `querySelector` is inherently first-match, so the hack is dropped.

---

## 3. Pointer / touch model

Legacy binds **`mousedown`** on each handle, then `mousemove`/`mouseup` on
`document`, and separately wires touch via Garnish's global touch→mouse shims. It
reads `ev.pageX/pageY` and gates on `Garnish.isPrimaryClick(ev)`.

**Recommendation: use the Pointer Events API** (`pointerdown` / `pointermove` /
`pointerup`), which unifies mouse + touch + pen and is universally supported in
every browser Craft 6 targets. This removes the entire legacy touch-shim surface.

Concretely:

- **Bind `pointerdown`** on the handle (replaces `mousedown`).
- On pointerdown, **`setPointerCapture(ev.pointerId)`** is *not* needed because we
  listen on `document` for move/up (capture-by-document is the legacy model and
  keeps delegation simple). Track `this._pointerId = ev.pointerId` and ignore
  move/up events from other pointer IDs (multi-touch safety).
- **Bind `pointermove` / `pointerup` on `doc`** (replaces the document
  mousemove/mouseup). Also bind **`pointercancel`** → treat as `pointerup`
  (touch interruption; legacy had no equivalent and could leak a stuck drag).
- **Primary-click gate:** `isPrimaryClick(ev)` already accepts `MouseEvent.button`;
  `PointerEvent` extends `MouseEvent`, so `ev.button === 0` works unchanged. Keep
  the existing `isPrimaryClick` call.
- **`touch-action`:** to stop the browser from scroll-panning while dragging, the
  handle should get `style.touchAction = 'none'` (or the consumer sets it via CSS).
  Note this in the Modal wiring and playground.

**Coordinates without jQuery `.offset()`:**

- `ev.pageX` / `ev.pageY` exist on `PointerEvent` — use them directly for
  `mousedownX/Y`, `mouseX/Y`, `realMouseX/Y` (parity with legacy).
- The target offset (for `mouseOffsetX/Y`) uses the existing
  `getOffset(this.$targetItem)` helper:
  ```ts
  const offset = getOffset(this.$targetItem); // {top, left} page-relative
  this.mouseOffsetX = ev.pageX - offset.left;
  this.mouseOffsetY = ev.pageY - offset.top;
  ```
- Axis locking is unchanged: only assign `mouseX` when `axis !== Y_AXIS`, only
  assign `mouseY` when `axis !== X_AXIS`; `realMouseX/Y` are always assigned.

**Method renames (private):** `_handleMouseDown` → `_handlePointerDown`,
`_handleMouseMove` → `_handlePointerMove`, `_handleMouseUp` → `_handlePointerUp`.
Keep the same body logic. (If the compat layer needs the old names it can alias.)

> **Risk:** the modern `addListener` wrapper builds a Garnish event object over the
> native event. `pageX/pageY/pointerId/button` are native props that pass straight
> through (the wrapper only *adds* `data`/`garnishTarget`), so reading them in the
> handlers is safe. Confirmed against `dom-listeners.ts::toGarnishEvent`.

---

## 4. Auto-scroll machinery → `src/utils/scroll.ts`

This is foundational and shared (Drag/DragSort reuse it), so it lives in a reusable
module, **not** inside `base-drag.ts`.

### 4.1 New module `src/utils/scroll.ts`

```ts
/** Axis-aware nearest-scrollable-ancestor finder (replaces jQuery .scrollParent()). */
export function getScrollParent(
  el: Element,
  axis?: 'x' | 'y' | null
): Element | Window;

/** True if the resolved scroll container is the window. */
export function isWindowScrollContainer(container: Element | Window): boolean;
```

`getScrollParent` reproduces `BaseDrag.setScrollContainer`'s walk:

1. Start at `el`'s nearest scroll parent (overflow auto/scroll/overlay ancestor).
2. If that is `documentElement` or `body`, return `win`.
3. Otherwise, if the container is scrollable **on the relevant axis**
   (`axis !== X_AXIS && scrollHeight > clientHeight`, or
   `axis !== Y_AXIS && scrollWidth > clientWidth`), it is the container.
4. Else, climb to the next scroll parent and repeat.

The existing private `getScrollParent` in `animation.ts` should be **deleted and
re-imported from here** so there is a single implementation. (Its current version
only checks `overflow-y`; the generalized one checks both axes — a strict superset,
so `scrollContainerToElement` keeps working.)

### 4.2 The drag-edge auto-scroll loop

Keep this **inside `base-drag.ts`** (it is drag-specific control flow), but have it
call the `scroll.ts` primitives for the actual scrolling. The loop:

- In `drag(didMouseMove)`, when `didMouseMove`, compute whether the pointer is
  within `windowScrollTargetSize` (25px) of a scrollable edge:
  - Vertical (unless `axis === X_AXIS`): edges from
    `isWindowScrollContainer` ? `[win.scrollY, win.scrollY + win.innerHeight]`
    : `[getOffset(container).top, +container.clientHeight]`. Inset both by 25px.
    If `mouseY` is outside the inset band → set `scrollProperty='scrollTop'`,
    `scrollAxis='Y'`, `scrollDist=round((mouseY - edge)/2)`.
  - Horizontal mirror (unless `axis === Y_AXIS`), only if vertical didn't already
    claim the scroll.
- If a scroll property is set and we weren't already scrolling, start a RAF loop
  `_scrollWindow()`. Cache `scrollProperty/Axis/Dist`. Else `_cancelWindowScroll()`.

`_scrollWindow()` each frame:

- Read current scroll pos: window → `win.scrollY/scrollX`; element → `el.scrollTop/scrollLeft`.
- Target = current + `scrollDist`, clamped to `[0, scrollMax]` where
  `scrollMax = scrollHeight - clientHeight` (Y) or `scrollWidth - clientWidth` (X).

  > **Behavior fix:** legacy used `outerHeight()` (border-box, includes border) for
  > the subtrahend, which is subtly wrong for a scroll container. The correct
  > value is `clientHeight`/`clientWidth`. Use `clientHeight`/`clientWidth`. Flag in
  > §9 as a deliberate deviation to validate in the playground.
- Apply: window → `win.scrollTo({top|left})`; element → assign `el.scrollTop/Left`.
- **Window-only correction:** when scrolling the window, adjust `mouseX/realMouseX`
  (or Y) by the delta so the dragged helper keeps tracking the cursor (legacy
  `this['mouse'+axis] -= scrollPos - newScrollPos`).
- Re-schedule itself via RAF and call `this.drag(true)` again.

`_cancelWindowScroll()` cancels the RAF and nulls `scrollProperty/Axis/Dist`.

> Store the resolved container on a private field (e.g. `this._scrollContainer:
> Element | Window`) instead of the legacy `this._.$scrollContainer` bag.

---

## 5. `DragMove`

Trivial subclass. Replaces the throwing stub in `src/drag-move.ts`. On each drag
frame it positions the target's top-left at the cursor minus the grab offset:

```ts
import {BaseDrag, type BaseDragSettings} from './drag/base-drag';

export class DragMove extends BaseDrag {
  override onDrag(): void {
    if (this.$targetItem) {
      this.$targetItem.style.left = `${this.mouseX! - this.mouseOffsetX!}px`;
      this.$targetItem.style.top = `${this.mouseY! - this.mouseOffsetY!}px`;
    }
    super.onDrag(); // preserve the trigger('drag') + settings.onDrag() (RAF) behavior
  }
}

export default DragMove;
```

> **Subtlety:** legacy `DragMove.onDrag` *replaced* `BaseDrag.onDrag` entirely and
> did **not** call super — so it set CSS but did **not** trigger the `drag` event or
> `settings.onDrag`. Decide:
> - **Option A (recommended):** call `super.onDrag()` so `DragMove` also emits the
>   `drag` event (strictly more capable; nothing legacy depended on the omission).
> - **Option B (strict parity):** don't call super (no event). Modal's draggable
>   path passes no `onDrag`, so either works for Modal.
>
> Recommend **A** and note the deviation. The CSS write must happen *synchronously*
> on each drag (not deferred), so write `left/top` before delegating to the RAF'd
> `super.onDrag()`.

---

## 6. Modal wiring — stop throwing, support `draggable` / `resizable`

`Modal.setContainer` currently throws for both options. Replace the two `throw`
blocks (`modal.ts` lines ~276–286) with the real wiring, mirroring legacy
`Modal.js` lines 79–96.

### 6.1 `draggable`

```ts
if (this.settings!.draggable) {
  this.dragger = new DragMove(this.$container, {
    handle: this.settings!.dragHandleSelector
      ? this.$container.querySelector(this.settings!.dragHandleSelector)
      : this.$container,
  });
}
```

- Type `dragger` as `DragMove | null` (currently `unknown`).
- `handle` accepts an `Element` (the container or the resolved handle) — matches the
  `BaseDragSettings.handle` union.

### 6.2 `resizable`

```ts
if (this.settings!.resizable) {
  const handle = document.createElement('div');
  handle.className = 'resizehandle';
  handle.innerHTML = ResizeHandle;          // import from ./icons/resize-handle
  this.$container.appendChild(handle);

  this.resizeDragger = new BaseDrag(handle, {
    onDragStart: () => this._handleResizeStart(),
    onDrag: () => this._handleResize(),
  });
}
```

- Type `resizeDragger` as `BaseDrag | null`.
- The two handlers already exist conceptually but need porting into `modal.ts`
  (they are not currently present — only the `resizeStartWidth/Height` fields are):

```ts
private _handleResizeStart(): void {
  this.resizeStartWidth = this.getWidth();
  this.resizeStartHeight = this.getHeight();
}

private _handleResize(): void {
  if (globals.ltr) {
    this.desiredWidth = this.resizeStartWidth! + this.resizeDragger!.mouseDistX! * 2;
  } else {
    this.desiredWidth = this.resizeStartWidth! - this.resizeDragger!.mouseDistX! * 2;
  }
  this.desiredHeight = this.resizeStartHeight! + this.resizeDragger!.mouseDistY! * 2;
  this.updateSizeAndPosition();
}
```

- `globals.ltr` already exists (`globals.ts`).
- `desiredWidth/Height` are already fields on `Modal` and feed
  `updateSizeAndPosition` (`modal.ts` lines 485–490) — no change needed there.

### 6.3 Destroy

`Modal.destroy` must tear down both draggers (legacy lines 407–413):

```ts
this.dragger?.destroy();
this.resizeDragger?.destroy();
```

Add these to the existing `override destroy()` before `super.destroy()`.

### 6.4 Core/BaseDrag surface Modal calls

| Modal call | Provided by |
| --- | --- |
| `new DragMove(container, {handle})` | `DragMove` ctor + `BaseDragSettings.handle` |
| `new BaseDrag(handle, {onDragStart, onDrag})` | `BaseDrag` ctor + settings callbacks |
| `this.resizeDragger.mouseDistX` / `.mouseDistY` | `BaseDrag` public props (§1.3) |
| `dragger.destroy()` / `resizeDragger.destroy()` | `BaseDrag.destroy()` (§1.4) |
| `globals.ltr` | `globals.ts` |
| `ResizeHandle` | `icons/resize-handle.ts` |

> Remove the `dragger: unknown` / `resizeDragger: unknown` placeholder typings and
> the two `throw` blocks. Update the class doc comment that says draggable/resizable
> are unsupported.

---

## 7. Testing strategy (happy-dom — no layout, no real pointer)

happy-dom returns `0` for `getBoundingClientRect`/`offsetWidth` and does not
synthesize pointer geometry, so split coverage:

**Unit-testable (Vitest, mock where noted):**

- **Settings/defaults merge** — construct with/without overrides; assert
  `settings` and the param-shift (`new BaseDrag(settingsObj)`).
- **Item management** — `addItems`/`removeItems`/`removeAllItems`/`getPrev`/`getNextItem`
  against a fake DOM; assert the `$items` array and the `WeakMap` registry
  (dedupe + "added to more than one dragger" warn path via a spy on `console.warn`).
- **Event wiring** — spy on `trigger` and the `settings.on*` callbacks; directly
  invoke `onBeforeDragStart/onDragStart/onDrag/onDragStop` (drive the RAF with a
  fake timer or by stubbing `requestAnimationFrame` to call synchronously) and
  assert event names + callback invocation order.
- **Pointer-down gating** — dispatch a synthetic `PointerEvent` (or a plain object
  with `button/pageX/pageY/ctrlKey`) and assert: secondary click ignored,
  `ignoreHandleSelector` match ignored, `$targetItem` captured, `mousedownX/Y` and
  `mouseOffsetX/Y` computed (mock `getOffset`/`getBoundingClientRect` to return a
  known rect).
- **Coordinate math** — feed scripted `pointermove` events with mocked `pageX/pageY`;
  assert axis locking (`X_AXIS`/`Y_AXIS`/both), `mouseDistX/Y`, and the
  `minMouseDist` threshold gate to `startDragging`.
- **`getScrollParent` logic** (`scroll.ts`) — build an ancestor chain with mocked
  `getComputedStyle` + `scrollHeight/clientHeight`; assert it returns the right node
  vs. `window`, and that `axis` narrows correctly.
- **Auto-scroll decision math** — call a small extracted helper (recommend
  factoring the "is the pointer in the edge band, and how far" computation into a
  pure function the loop calls) with mocked rects/scroll metrics; assert
  `scrollProperty/Axis/Dist`. This avoids needing a live RAF/scroll.
- **`DragMove.onDrag`** — set `mouseX/Y` + `mouseOffsetX/Y` + a fake `$targetItem`,
  call `onDrag`, assert `style.left/top`.
- **Modal `_handleResize`** — set a fake `resizeDragger` with `mouseDistX/Y`, stub
  `getWidth/getHeight`, assert `desiredWidth/Height` (both `ltr` and `rtl` via
  `globals.rtl`) and that `updateSizeAndPosition` is called.

**Manual (Vite playground) only — needs real layout/pointer:**

- End-to-end drag of a `DragMove` element following the cursor.
- Modal `draggable` (drag by handle / by container) and `resizable` (resize handle
  grows/shrinks symmetrically; RTL mirroring).
- The live auto-scroll loop near container/window edges (window correction keeping
  the helper under the cursor).
- `touch-action: none` behavior on touch devices.
- `pointercancel` cleanup (no stuck drag after a touch interruption).

> Provide a playground page (`playground/drag.html` or similar) with: a free
> `DragMove` box, a draggable+resizable Modal, and a tall scroll container, so all
> of the above can be eyeballed.

---

## 8. Proposed file layout & exports

```
src/
  drag/
    base-drag.ts        # BaseDrag class + BaseDragSettings + static defaults/tunables
  drag-move.ts          # REPLACE the throwing stub with `class DragMove extends BaseDrag`
  utils/
    scroll.ts           # getScrollParent(el, axis), isWindowScrollContainer()  (NEW)
    dom.ts              # + getOuterWidth/getOuterHeight (for downstream Drag)
```

- New folder `src/drag/` anticipates `drag/drag.ts` and `drag/drag-sort.ts` later;
  `BaseDrag` lives there. `DragMove` stays at `src/drag-move.ts` (its current path,
  already exported from the barrel) to avoid churn in `index.ts`.
- **`index.ts` named exports:** add `BaseDrag` and `BaseDragSettings`; `DragMove` is
  already exported. Re-export `getScrollParent` (and `getOuterWidth/Height`) through
  `src/utils/index.ts` → the barrel's `export * from './utils'`.
- **`Garnish` namespace object** (legacy-shaped, lower half of `index.ts`): add
  `BaseDrag` and `DragMove` properties so `new Garnish.BaseDrag(...)` /
  `new Garnish.DragMove(...)` resolve (this is exactly what legacy `Modal.js`
  calls, and what the compat layer relies on). `DragMove` is presumably already
  on the namespace given the stub export — verify and add `BaseDrag` alongside it.
- **`scroll.ts` ↔ `animation.ts`:** `animation.ts` deletes its private
  `getScrollParent` and imports from `scroll.ts` (single source of truth).

---

## 9. Hard-to-reproduce legacy behavior / risks

1. **Touch parity vs. Pointer Events.** Recommending Pointer Events drops the entire
   legacy touch-shim path. This is *better*, but it is a behavioral change: legacy
   synthesized mouse coords from `touchstart/move/end`. Pointer Events give us
   `pageX/pageY` natively, but anything in the legacy global touch shim that other
   code relied on is out of scope here. **Risk: low** for drag itself; validate on a
   touch device in the playground. Also requires `touch-action: none` on handles to
   prevent the browser eating the gesture — this is a new requirement consumers
   (incl. Modal) must honor.

2. **`scrollParent` edge cases.** jQuery's `.scrollParent()` has quirky rules
   (position:fixed handling, `overflow: hidden` ancestors, the `body`/`html`
   normalization). The native finder in §4.1 checks `overflow` auto/scroll/overlay +
   axis scrollability, which covers the common cases but is **not** byte-for-byte
   identical to jQuery UI's algorithm. **Risk: medium** for deeply nested scroll
   containers; the `Modal` use case (window scroll) is unaffected. Validate with the
   tall-scroll-container playground case.

3. **`clientHeight` vs legacy `outerHeight()` in scroll math (§4.2).** Deliberate
   correction. The legacy border-box value was subtly wrong; we use `clientHeight`/
   `clientWidth`. **Risk: low**, but eyeball the auto-scroll clamp at container
   bottoms to confirm it doesn't over/under-scroll.

4. **Window-scroll cursor correction.** The `mouseX -= scrollPos - newScrollPos`
   adjustment during window auto-scroll is fiddly and untestable in happy-dom
   (no real scroll). **Risk: medium** — must be validated live; a regression here
   manifests as the dragged helper drifting away from the cursor while edge-scrolling.

5. **RAF coalescing of hooks.** `onDragStart/onDrag/onDragStop` defer inside a RAF.
   If a subclass (or Modal's resize) expects the `onDrag` body to have run *before*
   the next pointermove, the deferral could reorder. Legacy had the same deferral,
   so parity is preserved — but `Modal._handleResize` reads `mouseDistX/Y`, which are
   set *synchronously* in `_handlePointerMove` before `drag()`/`onDrag()`, so the
   resize math stays correct. **Risk: low** (called out so it isn't "fixed" by
   accident).

6. **`DragMove` not calling `super.onDrag` in legacy (§5).** Choosing Option A
   (call super → also emit `drag`) is a minor behavioral addition. **Risk: trivial.**

7. **`$items` semantics change (jQuery collection → `Element[]`).** Downstream
   `Drag`/`DragSort` read `this.$items` and currently treat it as a jQuery object
   (`.length`, `.eq()`, `$.inArray`). When those are ported they must move to array
   indexing. The `BaseDrag` contract here uses `Element[]`; flag this so the `Drag`
   port doesn't assume a jQuery collection. The compat layer is the place to expose
   a jQuery-wrapped `$items` if any external plugin needs it.
```
