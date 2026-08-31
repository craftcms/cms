# API Reference

A concise "what can I call" cheat sheet for the public surface of
`@craftcms/garnish`. Signatures are simplified for readability; the source TSDoc
(and your editor's IntelliSense) is authoritative.

Two entry points:

| Import | Surface |
| --- | --- |
| `@craftcms/garnish` | Modern, jQuery-free named exports + the `Garnish` namespace object. |
| `@craftcms/garnish/compat` | Side-effecting: installs `window.Garnish`, restores `.extend()`/`this.base()`/jQuery args. |

---

## `Base`

`abstract class Base<S extends GarnishBaseSettings = GarnishBaseSettings>` — the
base class every component extends. Native `class` + `super`; no `init`/`this.base`
(those are compat-only).

| Member | Signature | Description |
| --- | --- | --- |
| `settings` | `S \| null` | Resolved settings; `null` until `setSettings` runs. |
| `disabled` | `get => boolean` | Whether the instance is disabled. |
| `setSettings` | `(settings?, defaults?) => void` | Shallow-merge defaults ← overrides into `settings`. |
| `on` | `(events, [data,] handler) => void` | Subscribe to instance event(s); supports `.namespace`s. `'*'` subscribes to **every** event. |
| `once` | `(events, [data,] handler) => void` | Like `on`, auto-removed after first fire. |
| `off` | `(events, handler?) => void` | Unsubscribe (namespaced, by type, and/or by handler ref). |
| `trigger` | `(type, data?) => void` | Emit an event to instance + class-level handlers. |
| `addListener` | `(elem, events, [data,] handler) => void` | Bind tracked DOM listener(s); `handler` may be a method-name string. |
| `removeListener` | `(elem, events) => void` | Remove specific DOM listener(s). |
| `removeAllListeners` | `(elem) => void` | Remove all DOM listeners on an element. |
| `enable` / `disable` | `() => void` | Toggle the DOM-listener gate. |
| `destroy` | `() => void` | Emit `destroy`, remove all listeners, clear handlers. |

Event objects passed to handlers are `GarnishEvent` (for DOM events, the native
`Event` augmented with `data`/`target`).

---

## `Modal`

`class Modal extends Base<ModalSettings>` — accessible, animated modal dialog.

**Constructor:** `new Modal(container?, settings?)` — also `new Modal(settings)`
(param shift when the first arg is a plain object).

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `Modal.instances` | `Modal[]` | All live modals. |
| `Modal.visibleModal` | `Modal \| null` | The currently visible modal. |
| `Modal.defaults` | `ModalSettings` | Default settings (below). |
| `Modal.relativeElemPadding` | `number` (`8`) | Legacy parity constant. |

### Settings (`ModalSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `autoShow` | `true` | Show immediately on construction (when a container is given). |
| `draggable` | `false` | Make the modal draggable (via `DragMove` on its container). |
| `dragHandleSelector` | `null` | Restrict dragging to this selector within the container (e.g. a header). |
| `resizable` | `false` | Add a corner resize handle (via `BaseDrag`). |
| `minGutter` | `10` | Min px between modal and viewport edge. |
| `onShow` / `onHide` | no-op | Show/hide callbacks. |
| `onFadeIn` / `onFadeOut` | no-op | Post-fade callbacks. |
| `closeOtherModals` | `false` | Hide any other visible modal on show. |
| `hideOnEsc` | `true` | Hide on Escape. |
| `hideOnShadeClick` | `true` | Hide on backdrop click. |
| `triggerElement` | `null` | Focus-restore target (defaults to focus at construction). |
| `shadeClass` | `'modal-shade'` | CSS class for the shade/backdrop. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `show` / `hide` | `() => void` / `(ev?) => void` | Show / hide with fade. |
| `quickShow` / `quickHide` | `() => void` | Show / hide without fade. |
| `onShow` / `onHide` / `onFadeIn` / `onFadeOut` | `() => void` | Overridable hooks (emit the event + run the callback). |
| `updateSizeAndPosition` | `() => void` | Re-center and re-fit; emits `updateSizeAndPosition`. |
| `getWidth` / `getHeight` | `() => number` | Measure the container (throws if unset). |
| `setContainer` | `(container) => void` | Assign/replace the container. |
| `addLiveRegion` | `() => void` | Append the live region to the container. |
| `destroy` | `() => void` | Remove DOM, drop from `instances`, base teardown. |
| `$container` / `$shade` / `$liveRegion` / `$triggerElement` | `HTMLElement \| Element \| null` | Element refs. |
| `visible` | `boolean` | Whether currently shown. |
| `desiredWidth` / `desiredHeight` | `number \| null` | Optional fixed dimensions. |
| `dragger` | `DragMove \| null` | The `DragMove` driving `draggable` (when enabled), else `null`. |
| `resizeDragger` | `BaseDrag \| null` | The `BaseDrag` driving `resizable` (on the resize handle), else `null`. |

**Events:** `show`, `hide`, `fadeIn`, `fadeOut`, `updateSizeAndPosition`, `escape`,
`destroy`.

---

## `HUD`

`class HUD extends Base<HUDSettings>` — an anchored, accessible popover/bubble with
smart 4-way positioning and scroll-follow. Attaches to a trigger element, picks the
best of four orientations from the available clearance, draws a tip pointing back at
the trigger, traps Tab focus between trigger and body, and registers a UI layer +
Escape shortcut (via the shared `UiLayerManager`, like `Modal`). Show/hide are
display toggles — legacy HUD does not animate.

**Constructor:** `new HUD(trigger, bodyContents?, settings?)` — also
`new HUD(trigger, settings)` (param shift when the 2nd arg is a plain object). This
is exactly the `FieldLayoutDesigner` call shape: `new HUD($addBtn, {...})`.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `HUD.instances` | `HUD[]` | All live HUDs. |
| `HUD.activeHUDs` | `Record<string, HUD>` | Open HUDs by namespace (powers `closeOtherHUDs`). |
| `HUD.tipClasses` | `Record<HUDOrientation, string>` | Orientation → tip-class suffix. |
| `HUD.defaults` | `HUDSettings` | Default settings (below). |

### Settings (`HUDSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `hudClass` | `'hud'` | Class(es) for the HUD container (multi-class string OK). |
| `tipClass` | `'tip'` | Base class for the tip; orientation suffix appended. |
| `shadeClass` | `'hud-shade'` | Class for the shade/backdrop. |
| `bodyClass` / `mainContainerClass` / `mainClass` | `'body'` / `'main-container'` / `'main'` | Body / scroll-wrapper / content classes. |
| `headerClass` / `footerClass` | `'hud-header'` / `'hud-footer'` | Header/footer hoisted out of the body content. |
| `orientations` | `['bottom','top','right','left']` | Sides to try, in order of preference. |
| `triggerSpacing` / `windowSpacing` | `10` / `10` | Gap to the trigger / viewport edge (px). |
| `tipWidth` | `30` | Tip/arrow width (px). |
| `minBodyWidth` / `minBodyHeight` | `200` / `0` | Min body dimensions (px). |
| `withShade` | `true` | Render a shade behind the HUD. |
| `onShow` / `onHide` / `onSubmit` | no-op | Registered as `show`/`hide`/`submit` handlers. |
| `closeBtn` | `null` | Element whose `activate` hides the HUD. |
| `listenToMainResize` | `true` | Reposition on the main element's `resize`. |
| `showOnInit` | `true` | Show immediately on construction. |
| `closeOtherHUDs` | `true` | Hide every other open HUD on show. |
| `hideOnEsc` | `true` | Hide on Escape. |
| `hideOnShadeClick` | `true` | Hide on shade click. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `show` / `hide` | `(ev?) => void` / `() => void` | Show / hide (display toggle); no-op if already in that state. |
| `toggle` | `() => void` | Flip visibility. |
| `showContainer` / `hideContainer` | `() => void` | The raw display toggles (overridable). |
| `updateBody` | `(contents) => void` | Replace body content; re-extract header/footer. |
| `updateRecords` | `() => boolean` | Re-measure; returns whether anything changed. |
| `updateSizeAndPosition` | `(force?) => void` | Schedule a RAF reposition. |
| `updateSizeAndPositionInternal` | `() => void` | The 4-way positioning pass. |
| `submit` | `() => void` | Fire the `submit` flow. |
| `onShow` / `onHide` / `onSubmit` | `() => void` | Overridable hooks (emit the event). |
| `destroy` | `() => void` | Remove DOM, drop from `instances`/`activeHUDs`, base teardown. |
| `$trigger` / `$hud` / `$tip` / `$body` / `$main` / `$mainContainer` / `$header` / `$footer` / `$shade` | `HTMLElement \| null` | Element refs (`$main` is where consumers append content). |
| `showing` | `boolean` | Whether currently shown. |
| `orientation` | `HUDOrientation \| null` | The chosen side after positioning. |

**Events:** `show`, `hide`, `submit`, `updateSizeAndPosition`, `destroy`.

```ts
import {HUD} from '@craftcms/garnish';

const hud = new HUD(addBtn, {
  hudClass: 'hud fld-library-hud',
  orientations: ['right', 'bottom', 'left'],
  showOnInit: false,
});
hud.on('show', () => populate(hud.$main!)); // $main is a raw HTMLElement
hud.on('hide', () => addBtn.focus());
addBtn.addEventListener('click', () => hud.show());
```

---

## `DisclosureMenu`

`class DisclosureMenu extends Base<DisclosureMenuSettings>` — the disclosure
dropdown/menu. Pairs a trigger button (`aria-controls` / `aria-expanded`) with a
pre-existing menu panel: anchors it below the trigger (flipping above when there's
no room) and aligns it left/center/right, manages keyboard nav + type-ahead
search and focus, registers a UI layer + Escape shortcut (via the shared
`UiLayerManager`, like `Modal`/`HUD`), dismisses on outside click, and exposes
item/group builders. Show is instant; hide fades out (WAAPI, reduced-motion aware).

**Constructor:** `new DisclosureMenu(trigger, settings?)` — `trigger` is an
`ElementInput` (coerced via `getElement`) carrying `aria-controls` (or with the
panel as its next sibling). No param shift (legacy `init(trigger, settings)` has no
body arg). This is the CP call shape: `new DisclosureMenu($trigger, {...})`.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `DisclosureMenu.instances` | `DisclosureMenu[]` | All live menus. |
| `DisclosureMenu.defaults` | `DisclosureMenuSettings` | Default settings (below). |
| `DisclosureMenu.getInstance(el)` | `(Element \| null) => DisclosureMenu \| undefined` | The menu registered for a trigger/container (native `$el.data('disclosureMenu')`). |

### Settings (`DisclosureMenuSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `position` | `null` | `'below'` forces the panel below; else clearance picks above/below. |
| `windowSpacing` | `5` | Min gap (px) to the viewport edge. |
| `withSearchInput` | `false` | Render a live item-filtering search input (also via `data-with-search-input`). |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `show` / `hide` | `() => void` | Show / hide; no-op if already in that state (show also no-ops on a `.disabled` trigger). |
| `handleTriggerClick` | `() => void` | Toggle (re-captures the alignment element). |
| `isExpanded` | `() => boolean` | Whether `aria-expanded === 'true'`. |
| `setContainerPosition` | `() => void` | Above/below + left/center/right positioning (also the scroll/resize handler). |
| `focusElement` | `(el \| 'prev' \| 'next') => void` | Focus an element, or move within the menu's focusables. |
| `addItem` / `addItems` | `(item(s), ul?, prepend?) => HTMLElement \| void` | Build + insert item(s); returns the item's element. |
| `createItem` | `(item) => HTMLLIElement` | Build an item `<li>` from a config (or pass an element through). |
| `addGroup` / `addList` / `addHr` | — | Add a group (`<h3>` + `<ul>`) / a bare `<ul>` / an `<hr>`. |
| `toggleItem` / `showItem` / `hideItem` / `removeItem` | `(el, show?) => void` | Item visibility. |
| `updateVisibility` / `hasVisibleItems` / `getFirstDestructiveGroup` / `isPadded` | — | Visibility/query helpers. |
| `clearSearchStr` | `() => void` | Reset the type-ahead buffer + timeout. |
| `destroy` | `() => void` | Drop registrations, remove from `instances`, base teardown. |
| `$trigger` / `$container` / `$alignmentElement` / `$searchInput` / `$nextFocusableElement` | `HTMLElement \| null` | Element refs. |
| `searchStr` | `string` | The current type-ahead buffer. |

**Events:** `beforeShow`, `show`, `hide`, `destroy`. Item selection is per-item
(`onActivate` / `callback` on `activate`, then the menu hides).

```ts
import {DisclosureMenu} from '@craftcms/garnish';

const menu = new DisclosureMenu(trigger); // trigger has aria-controls="#panel"
menu.addItem({label: 'Rename', onActivate: (el) => rename(el)});
menu.addItem({label: 'Delete', destructive: true, onActivate: () => remove()});
menu.on('show', () => console.log('opened'));
```

---

## `Listbox`

`class Listbox extends Base<ListboxSettings>` — a single-select toggle group.
Discovers its options from a container (`button` / `[type="button"]` /
`craft-button`), keeps exactly one option "pressed" at a time (`aria-pressed="true"`
+ the `selectedClass`), and notifies on change via the `onChange` setting and a
`change` event.

**Constructor:** `new Listbox(container?, settings?)` — also `new Listbox(settings)`
(param shift when the first arg is a plain object). Re-instantiating on a container
that already has a `Listbox` tears the old one down first.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `Listbox.defaults` | `ListboxSettings` | Default settings (below). |

### Settings (`ListboxSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `selectedClass` | `'active'` | Class applied to the selected option. |
| `focusClass` | `'focus'` | Class used for the focused option. |
| `onChange` | no-op | Called with `(option, index)` on selection change. |
| `readOnly` | `false` | Non-interactive: options aren't click-bound and leave the tab order. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `select` | `(index) => void` | Select the option at `index`; no-op if out of range or already selected. |
| `enable` / `disable` | `() => void` | Toggle `aria-disabled` on the container + the base DOM-listener gate. |
| `destroy` | `() => void` | Drop the container→instance mapping, then base teardown. |
| `$container` / `$selectedOption` | `HTMLElement \| null` | Container / selected-option refs. |
| `$options` | `HTMLElement[]` | The discovered option elements. |
| `selectedOptionIndex` | `number \| null` | Index of the selected option in `$options`. |

**Events:** `change` (with `{$selectedOption, selectedOptionIndex}`), plus inherited
`destroy`.

```ts
import {Listbox} from '@craftcms/garnish';

const listbox = new Listbox(document.querySelector('.listbox')!, {
  onChange: (option, index) => console.log('selected', index, option),
});
listbox.on('change', (ev) => console.log(ev.selectedOptionIndex));
// listbox.select(2);  listbox.destroy();
```

---

## `BaseDrag`

`class BaseDrag<S extends BaseDragSettings = BaseDragSettings> extends Base<S>` —
the jQuery-free drag foundation. Uses the Pointer Events API (mouse + touch + pen)
and a native RAF auto-scroll loop. Subclasses (`DragMove`, and later `Drag` /
`DragSort`) override the drag hooks to manipulate the dragged element(s).

**Constructor:** `new BaseDrag(items?, settings?)` — `items` is a selector,
element, or list of elements made draggable immediately; `settings` overrides the
defaults. Also `new BaseDrag(settings)` (param shift when the first arg is a plain
object).

> Drag handles require `touch-action: none` (CSS or inline) so the browser doesn't
> consume the gesture on touch devices. `Modal` sets this on its generated resize
> handle; for your own handles you must set it.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `BaseDrag.minMouseDist` | `number` (`1`) | Fallback when `settings.minMouseDist === null`. |
| `BaseDrag.windowScrollTargetSize` | `number` (`25`) | px edge band that triggers auto-scroll. |
| `BaseDrag.defaults` | `BaseDragSettings` | Default settings (below). |
| `getItemDragger(item)` | `(Element) => BaseDrag \| undefined` | Named export: read the dragger that owns an item. |

### Settings (`BaseDragSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `minMouseDist` | `null` | Min px the pointer must travel before a drag starts (`null` → `BaseDrag.minMouseDist`, i.e. `1`). |
| `handle` | `null` | Drag handle: element(s), a selector resolved within each item, a `(item) => handle` function, or `null` (the item itself). |
| `axis` | `null` | Restrict movement to `X_AXIS` (`'x'`), `Y_AXIS` (`'y'`), or `null` (both). |
| `ignoreHandleSelector` | `'input, textarea, button, select, .btn'` | Pointer-down on a descendant matching this is ignored (unless it IS the handle). |
| `onBeforeDragStart` / `onDragStart` / `onDrag` / `onDragStop` | no-op | Lifecycle callbacks (fire alongside the matching events). |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `$items` | `Element[]` | The draggable items (native elements; legacy held a jQuery collection). |
| `$targetItem` | `HTMLElement \| null` | The element currently being dragged, or `null`. |
| `dragging` | `boolean` | Whether a drag is in progress. |
| `mouseX` / `mouseY` | `number \| null` | Axis-constrained pointer page coords (what subclasses read). |
| `realMouseX` / `realMouseY` | `number \| null` | Unconstrained pointer page coords (before axis lock). |
| `mousedownX` / `mousedownY` | `number \| null` | Pointer-down page coords. |
| `mouseDistX` / `mouseDistY` | `number \| null` | `mouseX/Y - mousedownX/Y` (read by `Modal._handleResize`). |
| `mouseOffsetX` / `mouseOffsetY` | `number \| null` | Grab offset: pointer-down page coord minus the target's offset. |
| `scrollProperty` / `scrollAxis` / `scrollDist` / `scrollFrame` | `… \| null` | Auto-scroll loop state. |
| `addItems` / `removeItems` | `(items) => void` | Add/remove draggable items (binds/unbinds the handle listener). |
| `removeAllItems` | `() => void` | Stop tracking every item. |
| `getPrevItem` / `getNextItem` | `(item) => Element \| null` | Neighbor in `$items`. |
| `allowDragging` | `() => boolean` | Default `true`; override to gate dragging. |
| `startDragging` / `drag` / `stopDragging` | overridable | Drag lifecycle (subclasses call via `super`). |
| `setScrollContainer` / `isScrollingWindow` | `() => void` / `() => boolean` | Resolve / query the scroll container. |
| `onBeforeDragStart` | `() => void` | **Sync** hook: emits `beforeDragStart` + runs the callback. |
| `onDragStart` / `onDrag` / `onDragStop` | `() => void` | **RAF-deferred** hooks: emit the event + run the callback. |
| `destroy` | `() => void` | `removeAllItems()` then base teardown. |

**Events:** `beforeDragStart`, `dragStart`, `drag`, `dragStop`, plus inherited
`destroy`.

---

## `DragMove`

`class DragMove extends BaseDrag` — a trivial subclass whose only job is to set the
dragged element's `left`/`top` so it follows the cursor (minus the grab offset).
Used by `Modal`'s `draggable` option. Same constructor and surface as `BaseDrag`.

| Member | Signature | Description |
| --- | --- | --- |
| `onDrag` | `() => void` | Sets `$targetItem.style.left/top` synchronously, then calls `super.onDrag()` (so `DragMove` also emits the `drag` event — a deliberate improvement over legacy). |

---

## `Drag`

`class Drag<S extends DragSettings = DragSettings> extends BaseDrag<S>` — "picks
up" the selected element(s): snapshots the target geometry + draggee set, builds
floating *helper* clones that follow the cursor with lag, and animates them back
home (or fades them out) when the drag ends. It does **not** decide what to do with
a dragged element — that's `DragDrop` / `DragSort`.

**Constructor:** `new Drag(items?, settings?)` — same `items` shapes as `BaseDrag`;
also `new Drag(settings)` (param shift when the first arg is a plain object).

> Like `BaseDrag`, the dragged items / handles need `touch-action: none`.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `Drag.defaults` | `DragSettings` | Default settings (below). |

### Settings (`DragSettings`, extends `BaseDragSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `filter` | `null` | Which item(s) to drag: a `() => elements` fn, a selector that filters `$items`, or `null` → just `$targetItem`. |
| `singleHelper` | `false` | Build one helper for the whole draggee set instead of one per draggee. |
| `collapseDraggees` | `false` | Collapse the other draggees into the target (hide the rest). |
| `removeDraggee` | `false` | Hide every draggee (`display:none`) while dragging. |
| `hideDraggee` | `true` | Hide draggees via `visibility:hidden` while dragging. |
| `copyDraggeeInputValuesToHelper` | `false` | Copy `<input>`/`<select>`/`<textarea>` values from source into the clone. |
| `helperOpacity` | `1` | Helper opacity (`1` → no override). |
| `moveHelperToCursor` | `false` | Put the helper's top-left at the cursor instead of the grab offset. |
| `helper` | `null` | Helper wrapper: `(helper, index) => wrapped`, an element/markup to wrap into, or `null` (bare clone). |
| `helperBaseZindex` | `3000` | Base z-index for helpers — the `--c-z-drag` rung of the CP's stacking ladder. |
| `helperLagBase` | `3` | Base follow-lag divisor. |
| `helperLagIncrementDividend` | `1.5` | Per-helper lag increment dividend. |
| `helperSpacingX` / `helperSpacingY` | `5` / `5` | Per-index helper offset (px). |
| `onReturnHelpersToDraggees` | no-op | Callback fired (RAF-deferred) when helpers finish returning. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `$draggee` | `HTMLElement[]` | The dragged element(s); target item is always index 0. |
| `otherItems` / `totalOtherItems` | `Element[]` / `number \| null` | Items not in `$draggee`, and their count. |
| `helpers` | `HTMLElement[]` | The floating helper clones (native, not jQuery). |
| `helperTargets` / `helperPositions` | `Array<{left, top}>` | Per-helper target / current positions. |
| `targetItemWidth` / `targetItemHeight` / `targetItemPositionInDraggee` | `number \| null` | Snapshotted target geometry / index. |
| `draggeeVirtualMidpointX` / `draggeeVirtualMidpointY` | `number \| null` | Virtual midpoint of the draggee (read by `DragSort`). |
| `findDraggee` | `() => HTMLElement[]` | Resolve which items are dragged (`filter`/selector/`$targetItem`). |
| `setDraggee` / `appendDraggee` | `(draggee) => void` | Set / append the draggee set (creates helpers, applies hide/collapse). |
| `getHelperTargetX` / `getHelperTargetY` | `(real?) => number` | Helper target coord (`real` skips the `moveHelperToCursor` snap). |
| `returnHelpersToDraggees` | `() => void` | Animate every helper back to its source's offset, then show + remove (WAAPI, reduced-motion aware). |
| `fadeOutHelpers` | `() => void` | Fade each helper out and remove it (no return tween). |
| `allowDragging` | `() => boolean` | `false` while helpers are returning, else `true`. |
| `startDragging` / `drag` / `stopDragging` | overridable | Drag lifecycle (snapshot + helper build + lag loop). |
| `onReturnHelpersToDraggees` | `() => void` | **RAF-deferred** hook: emits `returnHelpersToDraggees` + runs the callback. |
| `destroy` | `() => void` | Cancel the lag loop + return anims, remove helpers, base teardown. |

**Events:** `returnHelpersToDraggees`, plus all inherited `BaseDrag` events
(`beforeDragStart`, `dragStart`, `drag`, `dragStop`, `destroy`).

> `Drag` does **not** auto-return helpers on drop — the consumer calls
> `returnHelpersToDraggees()` or `fadeOutHelpers()` from its own `dragStop`/
> `onDragStop` handler (legacy contract).

---

## `DragDrop`

`class DragDrop<S extends DragDropSettings = DragDropSettings> extends Drag<S>` —
adds drop targets + hit detection on top of `Drag`. Hit-testing reuses the
`hitTest` util (page coords), so this layer is thin.

**Constructor:** `new DragDrop(settings?)` — **settings only** (no positional
`items`); add draggable items afterwards with `addItems(...)`.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `DragDrop.defaults` | `DragDropSettings` | Default settings (below). |

### Settings (`DragDropSettings`, extends `DragSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `dropTargets` | `null` | Drop-target element(s), a selector, or a `() => elements` resolver. |
| `onDropTargetChange` | no-op | Called with the new active target (or `null`) whenever it changes. |
| `activeDropTargetClass` | `'active'` | Class toggled on the active drop target. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `$dropTargets` | `HTMLElement[] \| null` | Resolved drop targets (`null` when none). |
| `$activeDropTarget` | `HTMLElement \| null` | The element the cursor is over — a **raw `HTMLElement`, not jQuery** (no `[0]`). |
| `updateDropTargets` | `() => void` | Re-resolve `settings.dropTargets` into `$dropTargets`. |
| `onDragStart` / `onDrag` / `onDragStop` | overridable | Resolve targets / first-match hit-detect + class toggle / strip active class. |

**Hit detection:** first-match wins (iterate `$dropTargets` in order, `break` on the
first hit). `onDropTargetChange` fires only on a *change* (incl. target↔null).
**There is no `drop` event** — read `$activeDropTarget` in your own `dragStop`/
`onDragStop` handler to perform the drop. `DragDrop` inherits `Drag`'s helper/return
machinery and all `BaseDrag` events.

---

## `DragSort`

`class DragSort<S extends DragSortSettings = DragSortSettings> extends Drag<S>` — the
sortable-list dragger: drag items to reorder them within their container, with **live
insertion feedback** (the draggee is re-inserted into the DOM at the closest landing
spot as the cursor moves, and an optional `insertion` placeholder marks it). On drop
the new order is committed and `sortChange` fires if anything moved.

**Constructor:** `new DragSort(items?, settings?)` — same `items` shapes + plain-object
param-shift as `Drag`.

> Like `Drag`, the sortable items / handles need `touch-action: none`.

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `DragSort.defaults` | `DragSortSettings` | Default settings (below). |

### Settings (`DragSortSettings`, extends `DragSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `container` | `null` | The list container (element / selector / list). Walked up at drag start to the nearest ancestor that has a height; the drag only sorts while the cursor is over it. |
| `insertion` | `null` | Placeholder shown at the landing spot: a `(draggee) => element \| markup` fn, an element, an HTML string, or `null`. |
| `moveTargetItemToFront` | `false` | Move the target to the front of the draggee block at drag start (and leave it there on drop). |
| `magnetStrength` | `1` | Divides the helper's pull toward the cursor. `1` → exact tracking; `>1` → rubber-band toward the draggee's home. |
| `onInsertionPointChange` | no-op | Fired (RAF-deferred) whenever the insertion point moves. |
| `onSortChange` | no-op | Fired (RAF-deferred) on drop when the order actually changed. |
| `canInsertBefore` | `() => true` | Gate: may the draggee be inserted *before* this item? |
| `canInsertAfter` | `() => true` | Gate: may the draggee be inserted *after* this item? |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `$heightedContainer` | `HTMLElement \| null` | Resolved heighted container (the sort bounds). |
| `$insertion` | `HTMLElement \| null` | The placeholder element (raw, not jQuery). |
| `insertionVisible` | `boolean` | Whether the placeholder is in the DOM. |
| `closestItem` | `HTMLElement \| null` | The item the draggee is currently closest to. |
| `oldDraggeeIndexes` / `newDraggeeIndexes` | `number[] \| null` | Draggee indexes (into `$items`) at drag start / drop. |
| `createInsertion` | `() => HTMLElement \| null` | Build the placeholder from `settings.insertion`. |
| `canInsertBefore` / `canInsertAfter` | `(item) => boolean` | Insertion gates (delegate to settings). |
| `getHelperTargetX` / `getHelperTargetY` | `(real?) => number` | Helper target coord; applies `magnetStrength` rubber-banding. |
| `onInsertionPointChange` / `onSortChange` | `() => void` | **RAF-deferred** hooks: emit the event + run the callback. |
| `onDragStart` / `onDrag` / `onDragStop` | overridable | Record order + create insertion / closest-item detect + reflow / commit + return + `sortChange`. |

**Events:** `insertionPointChange`, `sortChange`, plus all inherited `Drag`/`BaseDrag`
events (`returnHelpersToDraggees`, `beforeDragStart`, `dragStart`, `drag`, `dragStop`,
`destroy`).

**Spatial hit-test (`_getClosestItem`):** distances are measured from the draggee's
virtual midpoint (axis-aware: `x`/`y`/Euclidean), walking outward from the draggee with
a monotonic-distance early-skip and `canInsertBefore`/`canInsertAfter` gating. Midpoints
are cached once per drag (`_precalculateMidpoints`) and only the moved item + neighbors
are recomputed per insertion; lists `> 200` items use a viewport filter. Unlike
`DragDrop`, `DragSort` **auto-returns** the helpers on drop.

---

## `Select`

`class Select<S extends SelectSettings = SelectSettings> extends Base<S>` — the
selection interface over a set of sibling items: click to select, shift-click to extend
a contiguous range, ctrl/⌘-click to toggle individual items (ctrl/shift roles swap under
`checkboxMode`). Selected items get the `selectedClass`; arrow keys move the selection
two-dimensionally by measuring item geometry, so it spans vertical lists and wrapping
grids. Commonly paired with `DragSort` to drag a multi-selection as a group.

**Constructor:** `new Select(container?, items?, settings?)` — with `new Select(settings)`
and `new Select(container, settings)` param-shifts (a plain-object arg is treated as the
settings).

### Statics

| Member | Type | Description |
| --- | --- | --- |
| `Select.defaults` | `SelectSettings` | Default settings (below). |

### Settings (`SelectSettings`)

| Key | Default | Description |
| --- | --- | --- |
| `selectedClass` | `'sel'` | Class added to selected items. |
| `checkboxClass` | `'checkbox'` | Class of a checkbox affordance inside an item; gets `aria-checked` toggled. |
| `multi` | `false` | Allow more than one item selected at once. |
| `allowEmpty` | `true` | Allow the selection to be emptied (container click / last-item deselect). |
| `vertical` / `horizontal` | `false` | Constrain arrow-key nav to a single column / row. |
| `handle` | `null` | What within each item receives the pointer listeners: selector / fn / element(s) / `null` (the item). |
| `filter` | `null` | Gate before selecting: a selector string or `(target) => boolean` predicate. |
| `checkboxMode` | `false` | Whether a checkbox affordance (not ctrl/shift) drives selection. |
| `makeFocusable` | `false` | Give the focused item a roving `tabindex` (keyboard-navigable list). |
| `waitForDoubleClicks` | `false` | Defer single-click (de)selection briefly so a double-click can pre-empt it. |
| `onSelectionChange` | no-op | Fired (RAF-deferred) whenever the selection changes. |

### Methods & properties

| Member | Signature | Description |
| --- | --- | --- |
| `$items` / `$selectedItems` | `HTMLElement[]` | Tracked items / the selected subset (native arrays). |
| `addItems` / `removeItems` | `(items) => void` | Track / untrack items (binds/unbinds their listeners). |
| `selectItem` / `selectRange` / `selectAll` | `(item?, …) => void` | Select one / a contiguous range / everything (range & all are `multi`-only). |
| `deselectItem` / `deselectAll` / `deselectOthers` | `(item?) => void` | Deselect one / all / all-but-one. |
| `toggleItem` | `(item, preventScroll?) => void` | Toggle a single item (honoring `allowEmpty`). |
| `isSelected` | `(item) => boolean` | Whether an item is selected. |
| `getSelectedItems` | `() => HTMLElement[]` | The selected items, as a fresh array. |
| `totalSelected` | `get => number` | How many are selected. |
| `resetItemOrder` | `() => void` | Re-sort `$items` / `$selectedItems` into current DOM order (after a reorder). |
| `focusItem` / `setFocusableItem` | `(item, …) => void` | Move DOM focus / set the roving-`tabindex` item. |

**Events:** `selectionChange` (RAF-deferred), `focusItem` (`{item}`), plus `Base`'s
`destroy`.

---

## `UiLayerManager`

`class UiLayerManager extends Base` — manages the stack of UI layers (document,
modals, HUDs, menus) and their scoped keyboard shortcuts. The active instance is a
singleton at `Garnish.uiLayerManager` (created by `initGarnish()`).

| Member | Signature | Description |
| --- | --- | --- |
| `layers` | `Layer[]` | The layer stack (index `0` = base document). |
| `layer` | `get => number` | Topmost layer index. |
| `currentLayer` | `get => Layer` | The active layer. |
| `modalLayers` / `highestModalLayer` | getters | Modal layers / the highest one. |
| `addLayer` | `(container?, options?) => this` | Push a layer; `{bubble}` opt-in for shortcut fall-through. |
| `removeLayer` | `(layer?) => this \| undefined` | Pop the top, or remove a specific layer. |
| `registerShortcut` | `(keyCode \| def, cb, layer?) => this` | Register a keyboard shortcut. |
| `unregisterShortcut` | `(keyCode \| def, layer?) => this` | Remove a shortcut. |
| `triggerShortcut` | `(ev, layerIndex?) => void` | Dispatch a matching shortcut (used internally). |

## `EscManager` — _deprecated_

`class EscManager extends Base` — legacy Escape-key dispatcher. **Deprecated**; use
`UiLayerManager`. `register(obj, fn)`, `unregister(obj)`, `escapeLatest(ev)`.

---

## The `Garnish` namespace object

`import {Garnish} from '@craftcms/garnish'` (also the default export). A single
object carrying every class, constant, utility, and flag — the legacy-shaped
singleton, for incremental migration. **Prefer the tree-shakeable named exports in
new code.**

- **Classes:** `Base`, `Modal`, `HUD`, `DisclosureMenu`, `Listbox`, `BaseDrag`,
  `Drag`, `DragDrop`, `DragSort`, `DragMove`, `UiLayerManager`, `EscManager`,
  `ShortcutManager` (_deprecated_ alias of `UiLayerManager`).
- **Globals/flags:** `win`, `doc`, `bod`, `scrollContainer` (get/set), `rtl`, `ltr`,
  `activateEventsMuted` (get/set), `resizeEventsMuted` (get/set).
- **Class-level events:** `on(Class, …)`, `off(Class, …)`, `once(Class, …)`.
- **Managers (after `initGarnish()`):** `escManager`, `uiLayerManager`.
- **Custom-event installers:** `installActivate`, `installTextchange`, `installResize`.
- **Icons:** `ResizeHandle` (SVG string).
- Plus every constant and utility listed below, and `muteResizeEvents(cb)`.

`initGarnish(): typeof Garnish` — lazily create + attach the manager singletons
(idempotent).

`muteResizeEvents(callback): void` — run `callback` with `resize` events suppressed.

---

## Utilities (named exports, also on `Garnish`)

### DOM (`utils/dom`)

| Function | Signature | Description |
| --- | --- | --- |
| `coerceElements` | `(input) => EventTarget[]` | jQuery-free `$(x)` coercion. |
| `getElement` | `(input) => EventTarget \| undefined` | First coerced element. |
| `hasAttr` | `(elem, attr) => boolean` | Whether the attribute is present. |
| `getOffset` | `(elem) => {top, left}` | Document-relative offset. |
| `hitTest` | `(x, y, elem) => boolean` | Whether a page point is inside the element. |
| `isCursorOver` | `(ev, elem) => boolean` | Whether the cursor is over the element. |
| `copyTextStyles` | `(source, target) => void` | Copy font/text CSS props. |

### Focusable matcher (`utils/focusable`)

| Function | Signature | Description |
| --- | --- | --- |
| `isFocusable` | `(el) => boolean` | Visible + focusable (incl. `tabindex="-1"`). |
| `isKeyboardFocusable` | `(el) => boolean` | Focusable and Tab-reachable. |
| `getFocusableElements` | `(container) => HTMLElement[]` | Focusable descendants, in order. |
| `isVisible` | `(el) => boolean` | jQuery-`:visible` equivalent. |

### Focus management (`utils/focus`)

| Function | Signature | Description |
| --- | --- | --- |
| `focusIsInside` | `(container) => boolean` | Whether focus is within. |
| `firstFocusableElement` | `(container) => HTMLElement \| null` | First focusable child. |
| `getKeyboardFocusableElements` | `(container) => HTMLElement[]` | Tab-reachable descendants. |
| `getFocusedElement` | `() => Element \| null` | `document.activeElement`. |
| `trapFocusWithin` | `(container) => void` | Install a Tab focus trap. |
| `releaseFocusWithin` | `(container) => void` | Remove the focus trap. |
| `setFocusWithin` | `(container) => void` | Focus the first sensible element (or the container). |

### ARIA (`utils/aria`)

| Function | Signature | Description |
| --- | --- | --- |
| `addModalAttributes` | `(container) => void` | Add `aria-modal`/`role="dialog"`. |
| `ariaHide` | `(element) => void` | `aria-hidden="true"` (restorable). |
| `hideModalBackgroundLayers` | `() => void` | Hide background siblings from AT. |
| `resetModalBackgroundLayerVisibility` | `() => void` | Reverse the above. |
| `isScriptOrStyleElement` | `(element) => boolean` | `<script>`/`<style>` test. |
| `hasJsAriaClass` | `(element) => boolean` | Whether it carries the bookkeeping class. |

### Forms (`utils/forms`)

| Function | Signature | Description |
| --- | --- | --- |
| `getInputBasename` | `(elem) => string \| null` | `name` with `[brackets]` stripped. |
| `getInputPostVal` | `(input) => string \| string[] \| null` | Value as it would POST. |
| `findInputs` | `(container) => HTMLElement[]` | Inputs within (legacy selector wart preserved). |
| `getPostData` | `(container) => Record<string,string>` | Serialize inputs to a POST map. |
| `copyInputValues` | `(source, target) => void` | Copy values input-by-input (skips files). |

### Animation / scroll (`utils/animation`)

| Function | Signature | Description |
| --- | --- | --- |
| `requestAnimationFrame` / `cancelAnimationFrame` | native | Re-exported native RAF. |
| `prefersReducedMotion` | `() => boolean` | Reduced-motion preference. |
| `getUserPreferredAnimationDuration` | `(duration) => number \| string` | `0` if reduced-motion, else the value. |
| `scrollContainerToElement` | `(container, elem?) => void` | Scroll an element into view. |
| `shake` | `(elem, prop?) => void` | WAAPI shake (no-op under reduced-motion). |

### Environment (`utils/env`)

| Function | Signature | Description |
| --- | --- | --- |
| `isMobileBrowser` | `(detectTablets?) => boolean` | UA sniff. **Deprecated** (prefer feature queries). |
| `isPrimaryClick` | `(ev) => boolean` | Primary click, no Ctrl/Meta. |
| `isCtrlKeyPressed` | `(ev) => boolean` | Platform Ctrl/⌘ pressed. |
| `getBodyScrollTop` | `() => number` | Clamped body scrollTop. |

### Misc (`utils/misc`)

| Function | Signature | Description |
| --- | --- | --- |
| `getDist` | `(x1,y1,x2,y2) => number` | Euclidean distance. |
| `within` | `(num,min,max) => number` | Clamp into range. |
| `isString` | `(val) => boolean` | String type guard. |
| `isArray` | `(val) => boolean` | **Deprecated** — use `Array.isArray`. |
| `isTextNode` | `(elem) => boolean` | Text-node test. |
| `log` | `(msg) => void` | **Deprecated** — use `console`. |
| `handleActivatingKeypress` | `(event, cb) => void` | **Deprecated** — use the `activate` event. |

### Custom-event installers (`custom-events`)

| Function | Signature | Description |
| --- | --- | --- |
| `installActivate` | `(el) => () => void` | Install unified click/keyboard `activate`; returns a disposer. |
| `installTextchange` | `(el, {delay?}) => () => void` | Install debounced `textchange`; returns a disposer. |
| `installResize` | `(el) => () => void` | Install dimension-change `resize`; returns a disposer. |

Reached transparently via `addListener(el, 'activate' | 'textchange' | 'resize', fn)`.

### Event system primitives (`events`, `dom-listeners`)

Mostly used through `Base`, but exported for advanced use:

- `EventEmitter` — per-instance object pub/sub (backs `Base.on/off/once/trigger`). An `on('*', …)` registration receives every triggered event (with its real `type`).
- `ClassEventBus` — class-level pub/sub (backs `Garnish.on/off/once`).
- `DomListenerRegistry` — namespaced DOM-listener registry (backs `addListener`).
- `parseEvents(events, splitOn?)`, `formatDomEvents(events, namespace)` — event-string helpers.
- Types: `GarnishEvent`, `GarnishEventHandler`, `DomListenerOptions`, `ElementInput`,
  `GarnishBaseSettings`, `ActivateOptions`, `TextchangeOptions`.

### Constants (`constants`)

Key codes (`ESC_KEY`, `TAB_KEY`, `RETURN_KEY`, `SPACE_KEY`, `A_KEY`, arrows, …),
mouse buttons (`PRIMARY_CLICK`, `SECONDARY_CLICK`), axes (`X_AXIS`, `Y_AXIS`),
durations (`FX_DURATION`, `SHAKE_STEPS`, `SHAKE_STEP_DURATION`), node types
(`TEXT_NODE`), and ARIA classes (`JS_ARIA_CLASS`, `JS_ARIA_TRUE_CLASS`,
`JS_ARIA_FALSE_CLASS`). Also `VERSION`.

### Globals (`globals`)

`win` (`window`), `doc` (`document`), `bod` (`document.body`) — native refs. The
jQuery-wrapped forms (`$win`/`$doc`/`$bod`/`$scrollContainer`) are **compat-only**.

---

## Compat exports (`@craftcms/garnish/compat`)

Importing the module is **side-effecting**: it builds the legacy-shaped namespace
and assigns `window.Garnish`.

| Export | Signature | Description |
| --- | --- | --- |
| `GarnishCompat` | object (default export) | The assembled legacy-shaped namespace (built on import). |
| `installGarnishCompat` | `() => GarnishCompatNamespace` | Build + install onto `window.Garnish` (guarded, idempotent). |
| `buildGarnishCompat` | `() => GarnishCompatNamespace` | Build the namespace **without** touching `window`. |
| `compatify` | `(ModernClass) => LegacyCtor` | Wrap a modern class so `.extend()` / `init` / `this.base()` work. |
| `resolveJQuery` | `() => JQueryLike \| null` | Detect global jQuery. |
| `isJquery` | `(val) => boolean` | Whether a value is a jQuery collection. |
| `toJq` | `(value) => JQueryCollection` | `$(value)` (throws if jQuery absent). |
| `unwrapJq` | `(value) => unknown` | jQuery collection → first native element. |

**`.extend()` shape:** `LegacyClass.extend(instanceMembers?, staticMembers?)` returns
a real subclass. Inside instance methods, `this.base(...)` calls the ancestor
implementation; `init(...)` runs as the constructor on the most-derived class.
Constructor arguments that are jQuery collections (or selectors) are unwrapped to
native elements before reaching the modern class.

**jQuery-only namespace members** restored by the compat layer: `isJquery`,
`$win`/`$doc`/`$bod`/`$scrollContainer` (lazy getters), a jQuery-wrapped
`getFocusedElement()`, the `$.fn.activate/textchange/resize` chaining sugar, and the
deprecated `Menu` (→ `CustomSelect`, when ported) / `ShortcutManager` (→
`UiLayerManager`) aliases. jQuery is required (and detected) at runtime for these;
absent jQuery, jQuery-only features throw a clear error and the rest degrades
gracefully.

---

## See also

- [`architecture.md`](architecture.md) — how the class/event/utility/settings systems
  fit together, and the behaviors worth knowing before you subclass.
- [`compat-and-migration.md`](compat-and-migration.md) — the `compat` entry, its
  legacy affordances, and how to migrate a plugin onto the modern surface.
- The package [`README`](../README.md) — install + per-component usage examples.
