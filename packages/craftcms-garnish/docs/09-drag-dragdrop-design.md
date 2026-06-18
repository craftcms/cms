# 09 — `Drag` / `DragDrop` Design (helpers, return-to-source, drop targets)

> **Status:** design contract only. This document specifies the TypeScript surface
> the implementation engineer should build for the legacy `Drag.js` (~460 LOC) and
> `DragDrop.js` (~115 LOC) ports. No implementation code is written here.
>
> **Scope:** port `Drag` (the "pick up the selected element(s)" layer that creates
> drag *helpers*, follows the cursor with lag, and animates the helpers back to
> their source on drop) and `DragDrop` (the "drop target" layer on top of `Drag`)
> onto the **already-shipped** modern `BaseDrag` (`src/drag/base-drag.ts`, see docs
> 07/08). `DragSort` is **out of scope** for this slice.
>
> **Grounding:** every modern signature below is written against the *real*
> `BaseDrag` API as implemented in `src/drag/base-drag.ts`, not the doc-07 sketch.
> Key facts the `Drag` port must build on:
>
> - `$items` is `Element[]`, `$targetItem` is `HTMLElement | null` (native, not
>   jQuery). Use `this.$items[i]` / `.indexOf` / `.length`, never `.eq()` /
>   `$.inArray`.
> - `BaseDrag` uses native `class extends` — **no `init()`/`this.base()` trampoline.**
>   Override with a real `constructor` + `super(...)` and call `super.method()`.
> - `startDragging` / `drag(didMouseMove)` / `stopDragging` / `onDragStart` /
>   `onDrag` / `onDragStop` / `allowDragging` are all overridable via super-dispatch.
> - `onDragStart`/`onDrag`/`onDragStop` already defer their *event emission* inside a
>   `requestAnimationFrame`. `mouseX/Y`, `mouseDistX/Y`, `mouseOffsetX/Y` are set
>   **synchronously** in `_handlePointerMove` *before* `drag()`/`onDrag()` run, so
>   helper-position math reads fresh coordinates.
> - `getItemDragger(item)` exposes the `WeakMap<Element, BaseDrag>` ownership for
>   tests; `DragDrop` does not need it.
> - Reserved (doc 08) property names `Drag` may now claim without collision:
>   `targetItemWidth`, `targetItemHeight`, `otherItems`, `draggeeDisplay`,
>   `$draggee`, `helpers`. `onReturnHelpersToDraggees` belongs to `Drag`.

---

## 0. Reuse inventory — what the modern core already provides

Do **not** re-create these; `Drag`/`DragDrop` consume them directly.

| Need | Already exists | Location |
| --- | --- | --- |
| Full pointer/drag lifecycle, auto-scroll, items, coords | `BaseDrag` | `src/drag/base-drag.ts` |
| `outerWidth`/`outerHeight` (border-box) | `getOuterWidth(el)` / `getOuterHeight(el)` | `src/utils/dom.ts` |
| Page-relative offset (`$.offset()`) | `getOffset(el): {top,left}` | `src/utils/dom.ts` |
| **Hit-test (page coords, replaces `Garnish.hitTest`)** | `hitTest(x, y, elem)` / `isCursorOver(ev, elem)` | `src/utils/dom.ts` |
| Copy form-input values src→clone (`Garnish.copyInputValues`) | `copyInputValues($source, $target)` | `src/utils/forms.ts` |
| `requestAnimationFrame` / `cancelAnimationFrame` | re-exported native | `src/utils/animation.ts` |
| Reduced-motion gate + preferred duration | `prefersReducedMotion()` / `getUserPreferredAnimationDuration()` | `src/utils/animation.ts` |
| `FX_DURATION` (200ms) | constant | `src/constants.ts` |
| `win` / `doc` / `bod` | refs | `src/globals.ts` |
| `activateEventsMuted`, `ltr`/`rtl` | mutable flags | `src/globals.ts` |
| Element coercion (`$(x)` → array) | `coerceElements` | `src/utils/dom.ts` |
| Settings/events/listeners base | `Base` (via `BaseDrag`) | `src/base.ts` |

`hitTest` in modern `utils/dom.ts` already mirrors the legacy `Garnish.hitTest`
exactly: it adds `scrollX/Y` and uses `getOffset` + `getOuterWidth/Height` to build
the page-coords bounding box. **No new hit-test code is needed** — `DragDrop` just
calls `hitTest(this.mouseX!, this.mouseY!, elem)`.

**Gaps that must be filled (new code):**

1. **WAAPI "return helpers to draggees" animation** — the legacy `$helper.velocity({left,top})`
   tween back to each source's offset, plus a `fadeOutHelpers` path. The modern core
   has no Velocity; reuse the WAAPI pattern Modal already established (`element.animate`,
   tracked + cancelable, reduced-motion-gated). See §3.
2. **Helper/clone creation without jQuery** — `.clone()`, name-stripping, sizing,
   z-index/opacity styling, and the `settings.helper` wrapper hook. See §2.
3. **A native `display`/`visibility` show/hide path** to replace jQuery `.hide()` /
   `.show()` / `.css('visibility', …)`. See §2.4.

---

## 1. `Drag` public contract

`Drag extends BaseDrag<DragSettings>`. It overrides the lifecycle to (a) snapshot
the target's size and the full draggee set, (b) build helper clones that follow the
cursor with lag, and (c) animate the helpers home on drop.

### 1.1 Constructor

Legacy `init(items, settings)` with the same param-shift `BaseDrag` already handles.
**`BaseDrag`'s constructor already implements the param-shift** (`new BaseDrag(settings)`
when the first arg is a plain object) and already calls `setSettings(settings, defaults)`.
`Drag` only needs to merge **its own** defaults on top, then delegate:

```ts
export class Drag<S extends DragSettings = DragSettings> extends BaseDrag<S> {
  constructor(items?: DragItemsInput | Partial<S> | null, settings?: Partial<S>) {
    // Re-run the same plain-object param-shift BaseDrag uses, so we can merge
    // Drag.defaults before super() applies them.
    let resolvedItems: DragItemsInput | Partial<S> | null = items ?? null;
    if (settings === undefined && isPlainObject(items)) {
      settings = items as Partial<S>;
      resolvedItems = null;
    }
    super(
      resolvedItems as DragItemsInput | null,
      {...(settings ?? {})} as Partial<S>
    );
    // Layer Drag.defaults *under* the caller's overrides without clobbering them.
    this.setSettings(this.settings as Partial<S>, Drag.defaults as Partial<S>);
  }
}
```

> **Settings-merge note.** `BaseDrag.setSettings(settings, BaseDrag.defaults)` runs
> inside `super()` and fills `BaseDrag` defaults. `Drag` then calls
> `setSettings(this.settings, Drag.defaults)` so its own keys get defaults while the
> already-merged caller overrides + BaseDrag keys survive. (`setSettings` on `Base`
> merges `defaults` *under* the first arg.) Verify the precedence against
> `Base.setSettings` during implementation; if precedence is wrong, build the merged
> object explicitly: `super(items, {...Drag.defaults, ...callerSettings})`. The
> explicit-merge form is the safer default and is recommended.

### 1.2 Settings (every `Drag` setting + default)

These extend `BaseDragSettings`. Defaults mirror legacy `Drag.defaults`.

```ts
export interface DragSettings extends BaseDragSettings {
  /** Which item(s) are actually dragged. `null` → just `$targetItem`. */
  filter: (() => ElementInput) | string | null;
  /** Build one helper for the whole draggee set instead of one per draggee. */
  singleHelper: boolean;
  /** Collapse multiple draggees into the target (hide the rest). */
  collapseDraggees: boolean;
  /** Hide the draggee entirely (display:none) while dragging. */
  removeDraggee: boolean;
  /** Hide the draggee via `visibility:hidden` while dragging. */
  hideDraggee: boolean;
  /** Copy `<input>`/`<select>`/`<textarea>` values from source into the clone. */
  copyDraggeeInputValuesToHelper: boolean;
  /** Helper opacity (1 → no opacity override). */
  helperOpacity: number;
  /** Position the helper's top-left at the cursor, not at the grab offset. */
  moveHelperToCursor: boolean;
  /**
   * Optional helper wrapper. A function `(helper, index) => wrappedHelper`, or an
   * element/markup the clone is appended into. `null` → use the bare clone.
   */
  helper:
    | ((helper: HTMLElement, index: number) => HTMLElement)
    | ElementInput
    | null;
  helperBaseZindex: number;
  helperLagBase: number;
  helperLagIncrementDividend: number;
  helperSpacingX: number;
  helperSpacingY: number;

  onReturnHelpersToDraggees: () => void;
}

static readonly defaults: DragSettings = {
  ...BaseDrag.defaults,
  filter: null,
  singleHelper: false,
  collapseDraggees: false,
  removeDraggee: false,
  hideDraggee: true,
  copyDraggeeInputValuesToHelper: false,
  helperOpacity: 1,
  moveHelperToCursor: false,
  helper: null,
  helperBaseZindex: 1000,
  helperLagBase: 3,
  helperLagIncrementDividend: 1.5,
  helperSpacingX: 5,
  helperSpacingY: 5,
  onReturnHelpersToDraggees: () => {},
};
```

### 1.3 Public properties (rename jQuery → native)

Legacy `Drag` exposed jQuery collections; modern keeps the `$`-prefixed names for
consumer parity but holds native types. **Helpers become `HTMLElement[]`**, not an
array of jQuery objects.

```ts
// Snapshot of the target geometry (read by drag()/DragSort)
targetItemWidth: number | null = null;            // getOuterWidth($targetItem)
targetItemHeight: number | null = null;           // getOuterHeight($targetItem)
targetItemPositionInDraggee: number | null = null;

/** The dragged element(s); target item is always index 0. */
$draggee: HTMLElement[] = [];

/** Items NOT in $draggee (everything else still tracked). */
otherItems: Element[] = [];
totalOtherItems: number | null = null;

/** The floating clones following the cursor. (legacy: jQuery[]) */
helpers: HTMLElement[] = [];
helperTargets: Array<{left: number; top: number}> = [];
helperPositions: Array<{left: number; top: number}> = [];
helperLagIncrement: number | null = null;

/** RAF handle for the lag-follow loop (replaces legacy updateHelperPosFrame). */
updateHelperPosFrame: number | null = null;

lastMouseX: number | null = null;
lastMouseY: number | null = null;

/** Set true while the return-to-source animation runs; gates allowDragging(). */
private _returningHelpersToDraggees = false;

/** Display value snapshotted off the target so we can restore it. */
draggeeDisplay: string | null = null;

/** Virtual midpoint of the draggee (read by DragSort / consumers). */
draggeeVirtualMidpointX: number | null = null;
draggeeVirtualMidpointY: number | null = null;
```

> Legacy used a bound `updateHelperPosProxy` + a function-property `_i/_j/_lag`
> micro-optimization (avoiding per-frame closures). In the modern port, use a plain
> arrow `() => this._updateHelperPos()` (RAF callback) and ordinary local `for`
> loop variables — the closure cost is negligible and the legacy pattern hurts
> readability and is untyped.

### 1.4 Public / overridable methods — mapped onto the real `BaseDrag`

```ts
// --- Lifecycle overrides (call super where noted) -------------------------

/** Block a new drag while helpers are animating home (legacy parity). */
override allowDragging(): boolean;        // return !this._returningHelpersToDraggees

/**
 * Snapshot target geometry + display, resolve the draggee set, build helpers,
 * start the lag-follow loop, then delegate the rest of start-up to BaseDrag.
 *
 * IMPORTANT (deviation from legacy): legacy Drag.startDragging *replaced*
 * BaseDrag.startDragging and re-implemented onBeforeDragStart/dragging/
 * setScrollContainer/onDragStart/activateEventsMuted inline. The modern
 * BaseDrag.startDragging already does all of that. So the override does only
 * the Drag-specific work, then calls super.startDragging() for the rest.
 */
override startDragging(): void;

/** Update draggeeVirtualMidpointX/Y from fresh coords, then super.drag(). */
override drag(didMouseMove: boolean): void;

/** Cancel the lag-follow RAF, then super.stopDragging(). */
override stopDragging(): void;

// --- Draggee management ---------------------------------------------------

/** Resolve which items are dragged (settings.filter | string | $targetItem). */
findDraggee(): HTMLElement[];

/** Set $draggee (target first), create helpers, apply hide/collapse styling. */
setDraggee(draggee: HTMLElement[]): void;

/** Append more draggees mid-drag (create helpers unless collapsed). */
appendDraggee(newDraggee: HTMLElement[]): void;

// --- Helper geometry ------------------------------------------------------

getHelperTargetX(real?: boolean): number;
getHelperTargetY(real?: boolean): number;

// --- Return / fade --------------------------------------------------------

/** Animate every helper back to its source's offset, then show + remove. */
returnHelpersToDraggees(): void;

/** Fade each helper out and remove it (no return tween). */
fadeOutHelpers(): void;

// --- Hook (event emitter) -------------------------------------------------

/** RAF-deferred: trigger('returnHelpersToDraggees') + settings callback. */
onReturnHelpersToDraggees(): void;
```

### 1.5 `startDragging` body — concrete, against the real super

```ts
override startDragging(): void {
  // Drag-specific snapshot (BaseDrag.startDragging does NOT do this).
  this.helpers = [];
  this.helperTargets = [];
  this.helperPositions = [];
  this.lastMouseX = this.lastMouseY = null;

  this.targetItemWidth = getOuterWidth(this.$targetItem!);
  this.targetItemHeight = getOuterHeight(this.$targetItem!);
  this.draggeeDisplay = getComputedStyle(this.$targetItem!).display;

  this.setDraggee(this.findDraggee());   // builds helpers

  // Everything not in the draggee set.
  this.otherItems = this.$items.filter((it) => !this.$draggee.includes(it as HTMLElement));
  this.totalOtherItems = this.otherItems.length;

  this.helperLagIncrement =
    this.helpers.length === 1
      ? 0
      : this.settings!.helperLagIncrementDividend / (this.helpers.length - 1);
  this.updateHelperPosFrame = requestAnimationFrame(() => this._updateHelperPos());

  // BaseDrag.startDragging: onBeforeDragStart(), dragging=true,
  // setScrollContainer(), onDragStart(), activateEventsMuted=true.
  super.startDragging();
}
```

> **Ordering subtlety.** Legacy called `onBeforeDragStart()` *first*, then did the
> snapshot. The modern `BaseDrag.startDragging` calls `onBeforeDragStart()` itself.
> Calling `super.startDragging()` *last* means our snapshot runs *before*
> `onBeforeDragStart` fires — the reverse of legacy. **This matters** only if a
> consumer's `onBeforeDragStart` callback inspects `$draggee`/`helpers`. Legacy fired
> `onBeforeDragStart` before helpers existed, so it could *not* see them either —
> meaning the safe, legacy-equivalent choice is to fire `onBeforeDragStart` before
> the snapshot. **Recommendation:** call `this.onBeforeDragStart()` explicitly first,
> do the snapshot, then call the rest of super's work. Since `BaseDrag.startDragging`
> bundles `onBeforeDragStart` with the rest, the cleanest port is to **not** call
> `super.startDragging()` and instead replicate its small body inline *minus*
> `onBeforeDragStart` (which we already called). Document whichever path is chosen;
> the inline-replication path is the faithful one. Pseudocode:
>
> ```ts
> override startDragging(): void {
>   this.onBeforeDragStart();              // legacy order: first
>   // ...Drag snapshot + helper build + lag loop...
>   this.dragging = true;
>   this.setScrollContainer();
>   this.onDragStart();
>   globals.activateEventsMuted = true;
> }
> ```
>
> This duplicates ~4 lines of `BaseDrag.startDragging` but preserves the exact
> legacy hook ordering. Flag the duplication in the impl notes. (If `BaseDrag` later
> grows a `protected _beginDragging()` that excludes `onBeforeDragStart`, switch to
> that.)

---

## 2. Helper / clone creation (`_createHelper`)

The legacy `_createHelper(index)` clones a draggee, sizes it, strips form `name`s,
optionally copies input values, optionally wraps it via `settings.helper`, appends
to `<body>`, and positions it absolutely with z-index/opacity. Native port:

```ts
private _createHelper(index: number): void {
  const draggee = this.$draggee[index];
  let helper = draggee.cloneNode(true) as HTMLElement;     // .clone() (deep)
  helper.classList.add('draghelper');

  if (draggee.closest('#content')) helper.classList.add('drag-in-content');
  if (draggee.closest('.slideout-container'))
    helper.classList.add('drag-in-slideout');

  if (this.settings!.copyDraggeeInputValuesToHelper) {
    copyInputValues(draggee, helper);                       // utils/forms.ts
  }

  // Blank every name= so cloned radios/checkboxes don't hijack form values.
  helper.querySelectorAll<HTMLElement>('[name]').forEach((el) =>
    el.setAttribute('name', '')
  );

  // Size to match source (border-box). Legacy used jQuery .outerWidth(n) which
  // sets content-box+padding+border; the native equivalent is width/height in
  // border-box. Set box-sizing:border-box + width/height = ceil(getOuterWidth).
  helper.style.boxSizing = 'border-box';
  helper.style.width = `${Math.ceil(getOuterWidth(draggee))}px`;
  helper.style.height = `${Math.ceil(getOuterHeight(draggee))}px`;
  helper.style.margin = '0';
  helper.style.pointerEvents = 'none';

  // Optional wrapper hook.
  if (this.settings!.helper) {
    if (typeof this.settings!.helper === 'function') {
      helper = this.settings!.helper(helper, index);
    } else {
      const wrapper = coerceElements(this.settings!.helper)[0] as HTMLElement;
      wrapper.appendChild(helper);
      helper = wrapper;
    }
  }

  bod.appendChild(helper);

  const pos = this._getHelperTarget(index, true);           // real=true: no cursor snap
  helper.style.position = 'absolute';
  helper.style.top = `${pos.top}px`;
  helper.style.left = `${pos.left}px`;
  helper.style.zIndex = String(
    this.settings!.helperBaseZindex + this.$draggee.length - index
  );
  helper.style.display = this.draggeeDisplay!;
  if (this.settings!.helperOpacity !== 1) {
    helper.style.opacity = String(this.settings!.helperOpacity);
  }

  this.helperPositions[index] = {top: pos.top, left: pos.left};
  this.helpers.push(helper);
}
```

### 2.1 `_getHelperTarget` / `getHelperTargetX|Y`

```ts
getHelperTargetX(real = false): number {
  if (!real && this.settings!.moveHelperToCursor) return this.mouseX!;
  return this.mouseX! - this.mouseOffsetX!;
}
getHelperTargetY(real = false): number {
  if (!real && this.settings!.moveHelperToCursor) return this.mouseY!;
  return this.mouseY! - this.mouseOffsetY!;
}

private _getHelperTarget(index: number, real = false): {left: number; top: number} {
  return {
    left: this.getHelperTargetX(real) + this.settings!.helperSpacingX * index,
    top: this.getHelperTargetY(real) + this.settings!.helperSpacingY * index,
  };
}
```

### 2.2 Lag-follow loop (`_updateHelperPos`)

Each RAF frame: recompute targets when the pointer moved, then ease each helper
toward its target by `1/lag` (a critically-damped-ish follow). Direct port, native
`.style` writes:

```ts
private _updateHelperPos(): void {
  if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY) {
    for (let i = 0; i < this.helpers.length; i++) {
      this.helperTargets[i] = this._getHelperTarget(i);     // real=false → may snap to cursor
    }
    this.lastMouseX = this.mouseX;
    this.lastMouseY = this.mouseY;
  }

  for (let j = 0; j < this.helpers.length; j++) {
    const lag = this.settings!.helperLagBase + this.helperLagIncrement! * j;
    const cur = this.helperPositions[j];
    const tgt = this.helperTargets[j];
    cur.left += (tgt.left - cur.left) / lag;
    cur.top += (tgt.top - cur.top) / lag;
    this.helpers[j].style.left = `${cur.left}px`;
    this.helpers[j].style.top = `${cur.top}px`;
  }

  this.updateHelperPosFrame = requestAnimationFrame(() => this._updateHelperPos());
}
```

> Cancel it in `stopDragging()` via `cancelAnimationFrame(this.updateHelperPosFrame!)`
> *before* `super.stopDragging()`.

### 2.3 `setDraggee` / `appendDraggee`

`setDraggee` records the target's index within the draggee set, forces the target to
index 0, builds helpers, and applies the hide/collapse styling. Native port replaces
jQuery `.add()`/`.not()`/`.hide()`/`.css('visibility')`:

```ts
setDraggee(draggee: HTMLElement[]): void {
  const target = this.$targetItem!;
  const all = draggee.includes(target) ? draggee : [...draggee, target];
  this.targetItemPositionInDraggee = all.indexOf(target);

  // Target first, then the rest (dedup target out).
  this.$draggee = [target, ...all.filter((el) => el !== target)];

  if (this.settings!.singleHelper) {
    this._createHelper(0);
  } else {
    for (let i = 0; i < this.$draggee.length; i++) this._createHelper(i);
  }

  if (this.settings!.removeDraggee) {
    this.$draggee.forEach((el) => this._hide(el));
  } else if (this.settings!.collapseDraggees) {
    target.style.visibility = 'hidden';
    this.$draggee.filter((el) => el !== target).forEach((el) => this._hide(el));
  } else if (this.settings!.hideDraggee) {
    this.$draggee.forEach((el) => (el.style.visibility = 'hidden'));
  }
}
```

`appendDraggee` follows the same structure (see legacy lines 154–179): early-return
on empty, snapshot `oldLength` unless collapsing, concat, create helpers for the new
indices unless collapsing, then hide the new draggees per the same three settings.

### 2.4 Native show/hide helpers

Replace jQuery `.hide()`/`.show()` (which save/restore the inline `display`):

```ts
/** jQuery .hide() — stash current display, set display:none. */
private _hide(el: HTMLElement): void {
  if (el.style.display !== 'none') {
    el.dataset.garnishOldDisplay = el.style.display;   // '' if none was inline
  }
  el.style.display = 'none';
}

/** jQuery .show() — restore the stashed inline display (or clear it). */
private _show(el: HTMLElement): void {
  el.style.display = el.dataset.garnishOldDisplay ?? '';
  delete el.dataset.garnishOldDisplay;
}
```

> `Drag` mostly toggles `visibility`, not `display`, for the draggees; `display:none`
> is only used in the `removeDraggee`/`collapseDraggees`-of-others paths. The
> `draggeeDisplay` snapshot (computed `display` of the target at drag start) is what
> gets re-applied to helpers and restored to draggees on return — keep that distinct
> from the `_hide`/`_show` inline-display stash.

---

## 3. Return-to-source animation (WAAPI, replaces Velocity)

This is the only genuinely new machinery. Legacy used
`$helper.velocity({left, top}, FX_DURATION, callback)` to tween each helper back to
its source draggee's offset, then `_showDraggee` removed the helpers and restored the
draggees. The modern core has no Velocity; **reuse the WAAPI pattern `Modal` already
uses** (`element.animate(...)`, tracked `Animation`, cancelable, reduced-motion gated,
`element.animate` may be absent in happy-dom).

### 3.1 `returnHelpersToDraggees`

```ts
returnHelpersToDraggees(): void {
  this._returningHelpersToDraggees = true;   // gates allowDragging()

  let pending = this.helpers.length;
  if (pending === 0) {
    this._showDraggee();
    return;
  }

  this.helpers.forEach((helper, i) => {
    const draggee = this.$draggee[i];

    // Restore the draggee's display now (it animates in *under* the helper).
    if (draggee) {
      draggee.style.display = this.draggeeDisplay!;
      draggee.style.visibility = this.settings!.hideDraggee ? 'hidden' : '';
    }

    const targetOffset = draggee ? getOffset(draggee) : {left: 0, top: 0};

    const finalize = () => {
      // Snap to final, then let _showDraggee remove all helpers at once.
      helper.style.left = `${targetOffset.left}px`;
      helper.style.top = `${targetOffset.top}px`;
      if (--pending === 0) this._showDraggee();
    };

    // Reduced-motion or no WAAPI → jump straight home.
    if (prefersReducedMotion() || typeof helper.animate !== 'function') {
      finalize();
      return;
    }

    const fromLeft = parseFloat(helper.style.left) || getOffset(helper).left;
    const fromTop = parseFloat(helper.style.top) || getOffset(helper).top;

    const anim = helper.animate(
      [
        {left: `${fromLeft}px`, top: `${fromTop}px`},
        {left: `${targetOffset.left}px`, top: `${targetOffset.top}px`},
      ],
      {duration: getUserPreferredAnimationDuration(FX_DURATION), fill: 'forwards'}
    );
    this._returnAnimations.push(anim);
    anim.addEventListener('finish', finalize, {once: true});
    anim.addEventListener('cancel', finalize, {once: true});
  });
}
```

> **WAAPI `left`/`top` caveat.** `element.animate` does not interpolate `left`/`top`
> as smoothly as `transform: translate()` and forces layout each frame. Legacy
> Velocity animated `left`/`top` too, so visual parity is preserved by using them —
> but the impl engineer **may** instead animate `transform: translate(dx, dy)` from
> the current to the target delta and commit the final `left`/`top` on finish (better
> perf, identical end state). Recommend `transform` and note the deviation; either is
> acceptable. Whichever is chosen, the **end state must set `left`/`top`** to the
> source offset so `_showDraggee` can remove the helper without a flash.

> **`getUserPreferredAnimationDuration(FX_DURATION)`** is the modern equivalent of
> the legacy fixed `FX_DURATION` — it already returns `0` under reduced-motion, so
> the explicit `prefersReducedMotion()` short-circuit above is belt-and-suspenders
> (matches how `modal.ts::_fade` is written). Keep both for clarity.

### 3.2 `_showDraggee` (completion)

```ts
private _showDraggee(): void {
  for (const helper of this.helpers) helper.remove();
  this.helpers = [];
  this._returnAnimations = [];

  // jQuery .show().css('visibility','') → restore display + clear visibility.
  this.$draggee.forEach((el) => {
    this._show(el);
    el.style.visibility = '';
  });

  this.onReturnHelpersToDraggees();
  this._returningHelpersToDraggees = false;
}
```

> Legacy only ran the `_showDraggee` completion off the **first** helper's tween
> callback (`i === 0`), assuming all tweens share the same duration and finish
> together. The port above is more robust: it counts down `pending` so completion
> fires after the **last** helper lands (or immediately under reduced motion). This
> is a deliberate, safe improvement — note it in the impl notes.

### 3.3 `fadeOutHelpers`

```ts
fadeOutHelpers(): void {
  this.helpers.forEach((helper) => {
    if (prefersReducedMotion() || typeof helper.animate !== 'function') {
      helper.remove();
      return;
    }
    const anim = helper.animate([{opacity: 1}, {opacity: 0}], {
      duration: getUserPreferredAnimationDuration(FX_DURATION),
      fill: 'forwards',
    });
    anim.addEventListener('finish', () => helper.remove(), {once: true});
    anim.addEventListener('cancel', () => helper.remove(), {once: true});
  });
}
```

### 3.4 `onReturnHelpersToDraggees` + animation tracking

```ts
/** RAF-deferred event hook (legacy parity). */
onReturnHelpersToDraggees(): void {
  requestAnimationFrame(() => {
    this.trigger('returnHelpersToDraggees');
    this.settings!.onReturnHelpersToDraggees();
  });
}

/** Tracked return animations so destroy()/a new drag can cancel mid-flight. */
private _returnAnimations: Animation[] = [];

override destroy(): void {
  if (this.updateHelperPosFrame) cancelAnimationFrame(this.updateHelperPosFrame);
  this._returnAnimations.forEach((a) => a.cancel());
  this.helpers.forEach((h) => h.remove());
  super.destroy();   // BaseDrag.destroy → removeAllItems() + Base teardown
}
```

---

## 4. jQuery removals — `Drag.js` + `DragDrop.js`

| # | Legacy | Native replacement |
| --- | --- | --- |
| 1 | `$.extend({}, Drag.defaults, settings)` | `setSettings` / explicit `{...Drag.defaults, ...settings}` |
| 2 | `$.isPlainObject(items)` param-shift | local `isPlainObject()` (same as `base-drag.ts`) |
| 3 | `this.$targetItem.outerWidth()/outerHeight()` | `getOuterWidth/Height($targetItem)` (`utils/dom.ts`) |
| 4 | `this.$targetItem.css('display')` | `getComputedStyle($targetItem).display` |
| 5 | `$.inArray(item, this.$draggee)` / `$.inArray(target, …)` | `array.indexOf(item)` / `.includes(item)` |
| 6 | `$([target].concat($draggee.not(target).toArray()))` | `[target, ...draggee.filter(el => el !== target)]` |
| 7 | `$draggee.hide()` / `.show()` | `_hide(el)` / `_show(el)` (§2.4) |
| 8 | `.css('visibility', 'hidden' \| '')` | `el.style.visibility = …` |
| 9 | `$draggee.clone()` | `draggee.cloneNode(true) as HTMLElement` |
| 10 | `$draggee.parents('#content').length` | `draggee.closest('#content')` (truthy) |
| 11 | `Garnish.copyInputValues($d, $h)` | `copyInputValues(draggee, helper)` (`utils/forms.ts`) |
| 12 | `$helper.find('[name]').attr('name','')` | `helper.querySelectorAll('[name]').forEach(el => el.setAttribute('name',''))` |
| 13 | `$helper.outerWidth(n).outerHeight(n).css(…)` | `style.boxSizing='border-box'` + `style.width/height` + `style.*` |
| 14 | `$(this.settings.helper).append($helper)` | `coerceElements(helper)[0].appendChild(clone)` |
| 15 | `$helper.appendTo(Garnish.$bod)` | `bod.appendChild(helper)` |
| 16 | `$helper.css({position, top, left, zIndex, display})` | discrete `helper.style.*` writes |
| 17 | `this.$draggee.eq(i)` | `this.$draggee[i]` |
| 18 | `$draggee.offset()` (return target) | `getOffset(draggee)` (`utils/dom.ts`) |
| 19 | `$helper.velocity({left,top}, FX_DURATION, cb)` | `helper.animate([...], {...})` + `finish`/`cancel` (§3) |
| 20 | `$draggeeHelper.velocity('fadeOut', {…})` | `helper.animate([{opacity:1},{opacity:0}], …)` (§3.3) |
| 21 | `Garnish.requestAnimationFrame/cancelAnimationFrame` | `requestAnimationFrame`/`cancelAnimationFrame` (`utils/animation.ts`) |
| 22 | `Garnish.activateEventsMuted = true` | handled by `super.startDragging()` (or `globals.activateEventsMuted`) |
| 23 | `$.noop` | `() => {}` |
| **DragDrop** | | |
| 24 | `$(this.settings.dropTargets)` / `$(fn())` | `coerceElements(...)` → `Element[]`; `null` if empty |
| 25 | `Garnish.hitTest(mouseX, mouseY, elem)` | `hitTest(this.mouseX!, this.mouseY!, elem)` (`utils/dom.ts`) |
| 26 | `this.$dropTargets[i]` loop | `for (const el of this.$dropTargets)` |
| 27 | `$activeDropTarget.addClass/removeClass(cls)` | `el.classList.add/remove(cls)` |
| 28 | `this.$activeDropTarget[0]` identity compare | compare `Element` refs directly (no `[0]`) |
| 29 | `$(elem).addClass(cls)` then store | `el.classList.add(cls)`; store the raw `Element` |

---

## 5. `DragDrop` public contract

`DragDrop extends Drag<DragDropSettings>`. It adds drop targets and active-target
tracking. Because hit-testing is already provided by `hitTest`, this layer is thin.

### 5.1 Settings

```ts
export interface DragDropSettings extends DragSettings {
  /** Drop-target element(s), a selector, or a resolver function. */
  dropTargets: ElementInput | (() => ElementInput) | null;
  /** Called whenever the active drop target changes (with the new one or null). */
  onDropTargetChange: (activeDropTarget: HTMLElement | null) => void;
  /** Class toggled on the active drop target. */
  activeDropTargetClass: string;
}

static readonly defaults: DragDropSettings = {
  ...Drag.defaults,
  dropTargets: null,
  onDropTargetChange: () => {},
  activeDropTargetClass: 'active',
};
```

### 5.2 Properties (native rename)

```ts
/** Resolved drop targets, or null when none. (legacy: jQuery | null) */
$dropTargets: HTMLElement[] | null = null;

/** The element the cursor is currently over, or null. (legacy: jQuery | null) */
$activeDropTarget: HTMLElement | null = null;
```

> **Rename note:** legacy `$activeDropTarget` was a jQuery object and consumers read
> `this.$activeDropTarget[0]`. Modern holds a single `HTMLElement | null` directly —
> consumers must drop the `[0]`. Flag this for the compat layer.

### 5.3 Methods (mapped onto the real super-dispatch)

```ts
constructor(settings?: Partial<DragDropSettings>) {
  super(null, {...DragDrop.defaults, ...(settings ?? {})});
  // DragDrop's legacy init only takes (settings); items come via addItems later.
}

/** Resolve settings.dropTargets → HTMLElement[] | null (null if empty). */
updateDropTargets(): void {
  const dt = this.settings!.dropTargets;
  if (!dt) { this.$dropTargets = null; return; }
  const resolved = (typeof dt === 'function'
    ? coerceElements(dt())
    : coerceElements(dt)
  ).filter((el): el is HTMLElement => el instanceof HTMLElement);
  this.$dropTargets = resolved.length ? resolved : null;
}

override onDragStart(): void {
  this.updateDropTargets();
  this.$activeDropTarget = null;
  super.onDragStart();
}

override onDrag(): void {
  if (this.$dropTargets) {
    let active: HTMLElement | null = null;
    for (const el of this.$dropTargets) {
      if (hitTest(this.mouseX!, this.mouseY!, el)) { active = el; break; }
    }

    // Did the active target change?
    if (active !== this.$activeDropTarget) {
      if (this.$activeDropTarget) {
        this.$activeDropTarget.classList.remove(this.settings!.activeDropTargetClass);
      }
      this.$activeDropTarget = active;
      if (active) active.classList.add(this.settings!.activeDropTargetClass);
      this.settings!.onDropTargetChange(this.$activeDropTarget);
    }
  }
  super.onDrag();   // Drag.onDrag → BaseDrag.onDrag (RAF event emit)
}

override onDragStop(): void {
  if (this.$dropTargets && this.$activeDropTarget) {
    this.$activeDropTarget.classList.remove(this.settings!.activeDropTargetClass);
  }
  super.onDragStop();
}
```

### 5.4 Hit-detection contract

- **Coordinate space:** `this.mouseX/mouseY` are **page coords** (set from
  `ev.pageX/pageY` in `BaseDrag._handlePointerMove`). `hitTest(x, y, elem)` in
  `utils/dom.ts` also works in page coords (it adds `scrollX/Y` to the element's
  `getBoundingClientRect`). So the spaces match — pass `mouseX/mouseY` straight in.
- **First-match wins:** like legacy, iterate `$dropTargets` in order and `break` on
  the first hit. Overlapping targets resolve to the earliest in the list.
- **Change detection:** identity comparison of `Element` refs (no `[0]` unwrap).
  `onDropTargetChange` fires only on a *change*, including target→null and
  null→target transitions.
- **Cleanup:** `onDragStop` strips the active class. There is no separate "drop"
  event in legacy `DragDrop` — the consumer reads `$activeDropTarget` inside its own
  `dragStop`/`onDragStop` handler to perform the drop. Preserve that contract (do
  **not** invent an `onDrop` here).

> `Drag.onDrag` (the immediate super) does **not** override `BaseDrag.onDrag` in
> legacy — only `DragDrop` does. So `DragDrop.onDrag`'s `super.onDrag()` resolves to
> `BaseDrag.onDrag` (RAF-deferred trigger). Confirm the modern `Drag` does **not**
> add its own `onDrag` override (it shouldn't — `Drag`'s per-frame work is in
> `_updateHelperPos`, not `onDrag`), so the chain stays `DragDrop → BaseDrag`.

---

## 6. File layout & exports

```
src/
  drag/
    base-drag.ts     # EXISTS — BaseDrag (docs 07/08)
    drag.ts          # NEW — class Drag extends BaseDrag + DragSettings
    drag-drop.ts     # NEW — class DragDrop extends Drag + DragDropSettings
  drag-move.ts       # EXISTS — DragMove
```

- **`src/drag/drag.ts`** exports `Drag` (default + named), `DragSettings`, and the
  reserved-name types. **`src/drag/drag-drop.ts`** exports `DragDrop` (default +
  named) and `DragDropSettings`.
- **`src/index.ts` named exports:** add `Drag`, `DragSettings`, `DragDrop`,
  `DragDropSettings` alongside the existing `BaseDrag`/`DragMove` exports.
- **`Garnish` namespace object** (lower half of `index.ts`): add `Drag` and
  `DragDrop` so `new Garnish.Drag(...)` / `new Garnish.DragDrop(...)` resolve — this
  is what legacy `DragDrop.js` (`Garnish.DragDrop.defaults`) and downstream plugins
  call, and what the compat layer relies on. Mirror how `BaseDrag`/`DragMove` were
  added in doc 08.
- **No new utils.** Everything `Drag`/`DragDrop` need (`getOuterWidth/Height`,
  `getOffset`, `hitTest`, `copyInputValues`, `coerceElements`,
  `requestAnimationFrame`, `prefersReducedMotion`,
  `getUserPreferredAnimationDuration`) already exists and is barrel-exported.
- **`isPlainObject`:** `base-drag.ts` keeps this private. Either re-export it from a
  shared `utils/misc.ts` for `drag.ts` to reuse, or duplicate the tiny helper. The
  doc-08 impl left it module-private in `base-drag.ts`; **recommend** promoting it to
  `utils/misc.ts` and having `base-drag.ts` import it, so `drag.ts` shares one copy.

---

## 7. Testing strategy (happy-dom — no layout, no real pointer)

Mirror the doc-07/08 split. happy-dom returns `0` for geometry and **`element.animate`
is `undefined`** (so the reduced-motion / no-WAAPI fallback path is the one exercised
in tests — which is convenient: return-to-source resolves synchronously).

**Unit-testable (Vitest):**

- **Settings/defaults merge** — `Drag.defaults` and `DragDropSettings` defaults
  applied; param-shift (`new Drag(settingsObj)`); confirm BaseDrag keys survive.
- **`findDraggee`** — `filter` as function, as selector string (filter `$items`),
  and `null` (→ `[$targetItem]`).
- **`setDraggee`** — target forced to index 0; `targetItemPositionInDraggee`
  computed; `_createHelper` called once (`singleHelper`) vs per-draggee; the three
  hide/collapse styling branches (`removeDraggee`/`collapseDraggees`/`hideDraggee`)
  set the right `display`/`visibility`. Spy on `_createHelper`.
- **`_createHelper`** — clone gets `draghelper` class; `[name]` attrs blanked;
  `copyInputValues` invoked when `copyDraggeeInputValuesToHelper`; z-index/opacity/
  position styles; `settings.helper` function-wrapper and element-wrapper paths;
  appended to `bod`. (Mock `getOuterWidth/Height` since happy-dom returns 0.)
- **`getHelperTargetX/Y` + `_getHelperTarget`** — `real` flag, `moveHelperToCursor`,
  per-index spacing. Set `mouseX/Y` + `mouseOffsetX/Y` directly.
- **`_updateHelperPos`** — set `helperPositions`/`helperTargets`/`helperLagIncrement`
  + `mouseX/Y`, run one frame (stub RAF synchronous), assert eased `style.left/top`
  and that targets recompute only when the mouse moved.
- **`startDragging`/`stopDragging` ordering** — spy on `onBeforeDragStart`,
  `setDraggee`, `super.startDragging` (or the inlined body), and the lag-RAF
  start/cancel. Assert `otherItems`/`totalOtherItems`. Assert `stopDragging` cancels
  `updateHelperPosFrame` before super.
- **`drag`** — `draggeeVirtualMidpointX/Y` computed from `mouseX - mouseOffsetX +
  targetItemWidth/2` before `super.drag`.
- **`returnHelpersToDraggees` / `_showDraggee` (reduced-motion path)** — since
  `element.animate` is undefined, the `finalize()` path runs sync: draggees restored
  (`display`/`visibility`), helpers removed, `_returningHelpersToDraggees` toggled
  true→false, `onReturnHelpersToDraggees` event fired (stub RAF). Also test the
  `helpers.length === 0` early `_showDraggee`. Mock `prefersReducedMotion`→`true`
  to test the explicit short-circuit too.
- **`fadeOutHelpers`** — reduced-motion path removes helpers immediately.
- **`allowDragging`** — `false` while `_returningHelpersToDraggees`, else `true`.
- **`destroy`** — cancels lag RAF, cancels tracked return anims (push a fake
  `{cancel: vi.fn()}` into `_returnAnimations`), removes helpers, calls super.
- **DragDrop `updateDropTargets`** — element list, selector, resolver fn, and the
  empty→`null` collapse.
- **DragDrop `onDrag` hit-detection** — mock `hitTest` to return true for a chosen
  element; assert first-match `break`, class add/remove on change, `onDropTargetChange`
  fires only on change (target↔null↔target transitions), identity compare (no `[0]`).
- **DragDrop `onDragStop`** — strips active class when a target is active.

**Manual (Vite playground) only — needs real layout/pointer/animation:**

- Helpers actually cloning, sizing to source, and lag-following the cursor (incl.
  multi-helper spacing/lag and `singleHelper`).
- `moveHelperToCursor`, `helperOpacity`, custom `settings.helper` wrapper visuals.
- **Return-to-source WAAPI tween** landing exactly on each draggee's offset with no
  flash before removal (the path happy-dom can't run); `fadeOutHelpers`.
- Reduced-motion: helpers jump home instead of tweening.
- DragDrop active-target highlighting while dragging over real, laid-out targets;
  overlap/first-match resolution; auto-scroll + drop-target interplay.
- `copyDraggeeInputValuesToHelper` preserving real input/radio/checkbox state.

> Recommend extending the (still-unbuilt) drag playground page from doc 08 with: a
> multi-item `Drag` with helpers + return animation, and a `DragDrop` with several
> drop targets.

---

## 8. Hard-to-reproduce legacy behavior / risks

1. **WAAPI `left`/`top` vs `transform` (§3.1).** Velocity tweened `left`/`top`;
   WAAPI can too but it's layout-thrashy. If the impl switches to `transform` for
   perf, the **end state must commit `left`/`top`** so `_showDraggee` removes helpers
   without a flash. **Risk: medium** — validate the landing is pixel-exact in the
   playground.

2. **Completion fires on last helper, not first (§3.2).** Legacy keyed `_showDraggee`
   off helper 0's callback. The port counts pending tweens. Equivalent when durations
   match (they do), more robust otherwise. **Risk: low** — but if a custom
   `settings.helper` wrapper changes a helper's animation, the countdown is the safer
   model.

3. **`startDragging` hook ordering (§1.5).** Legacy fires `onBeforeDragStart` *before*
   building helpers; the naive `super.startDragging()`-last port reverses it. Use the
   inline-replication path (call `onBeforeDragStart` first) to preserve order.
   **Risk: medium** if any consumer's `onBeforeDragStart` mutates the DOM that helper
   cloning then snapshots.

4. **`outerWidth(n)` border-box semantics (§2).** jQuery `.outerWidth(n)` *sets* the
   total box; the native equivalent needs `box-sizing: border-box` + `width`. If the
   cloned element has a different `box-sizing` than the source, sizing could drift.
   **Risk: low–medium** — eyeball helper size vs. source in the playground.

5. **`name=""` blanking on clones (§2).** Required so cloned radios/checkboxes don't
   steal posted values from the originals. `querySelectorAll('[name]')` must run on
   the *clone* before it's appended. **Risk: low** but security/data-relevant — keep
   the test that asserts every `[name]` is blanked.

6. **`copyInputValues` clone alignment.** The modern `copyInputValues(source, target)`
   must walk source/clone inputs in the same order the legacy jQuery version did
   (index-aligned). Verify the `utils/forms.ts` implementation matches before relying
   on it for `copyDraggeeInputValuesToHelper`. **Risk: low** (it's already a ported,
   tested util) — but add a focused test.

7. **`$activeDropTarget` shape change (jQuery → `HTMLElement`).** Consumers reading
   `this.$activeDropTarget[0]` break. The compat layer should expose a jQuery-wrapped
   accessor if any external plugin needs it; core/Vue consumers use the raw element.
   **Risk: medium** for external plugins; flag in the compat layer.

8. **`$items`/`$draggee` as `Element[]`.** Same caveat as doc 08 §risk 7 — no `.eq()`,
   `.length` on a real array is fine, `$.inArray` → `.indexOf`/`.includes`. **Risk:
   low** within this port (we control it); relevant for `DragSort` next.

9. **`getUserPreferredAnimationDuration` returning 0.** Under reduced motion the
   duration is 0; the WAAPI animation still fires `finish` synchronously-ish. The
   explicit `prefersReducedMotion()` short-circuit avoids relying on a 0-duration
   animation firing — keep both paths. **Risk: low.**

10. **Lag-follow loop lifetime.** The `_updateHelperPos` RAF must be canceled in
    `stopDragging` **and** `destroy`; a leaked loop keeps writing styles to removed
    helpers. **Risk: low** but a classic leak — covered by the destroy/stop tests.
