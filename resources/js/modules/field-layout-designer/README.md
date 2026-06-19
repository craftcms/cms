# Field Layout Designer (FLD)

A native, jQuery-free port of the legacy jQuery `Craft.FieldLayoutDesigner`
(`packages/craftcms-legacy/cp/src/js/FieldLayoutDesigner.js`) onto the modern
**`@craftcms/garnish`** package. FLD's own DOM/data work is plain DOM + WeakMaps;
jQuery (`$`) survives **only** at the boundary with Craft's still-jQuery widgets.

## What changed

- **Class system.** `Garnish.Base.extend({...})` → `class extends Base`,
  `Garnish.Drag.extend({...})` → `class extends Drag`, `init()` →
  `constructor()`, `this.base(...)` → `super.method(...)`, `new Garnish.HUD(...)`
  → `new HUD(...)`. Garnish utilities/constants
  (`hasAttr`, `getDist`, `hitTest`, `getOffset`, `getOuterWidth/Height`,
  `firstFocusableElement`, `prefersReducedMotion`,
  `getUserPreferredAnimationDuration`, `requestAnimationFrame`, `FX_DURATION`,
  `ESC_KEY`, `RETURN_KEY`, `bod`) are named imports.
- **All of FLD's own jQuery is now native DOM:**
  - `$('<div>')` / `$(html)` → `document.createElement` / a `<template>`-based
    `htmlToElement()` helper.
  - `.addClass/.removeClass/.hasClass` → `classList`; `.is('.x')`/`.not()` →
    `matches()`/`filter`.
  - `.text()` → `textContent`; `.html()` → `innerHTML`; `.val()` → `.value`.
  - tree ops (`.append/.before/.after/.detach/.remove`,
    `.children/.find/.closest/.parent/.siblings/.next/.prev`) → native `append`/
    `before`/`after`/`remove`/`children`/`querySelector(All)`/`closest`/
    `parentElement`/`*ElementSibling`. jQuery `.children(sel)` becomes
    `querySelectorAll(':scope > sel')`; `.find(sel)` becomes the bare
    `querySelectorAll(sel)`.
  - `.attr/.removeAttr` → `get/set/removeAttribute`; `.prop` → property;
    `.css()` → `.style`; `.offset()`/`.outerHeight()` → `getOffset`/
    `getOuterHeight` (and `clientHeight`-based content height where the legacy
    used `.height()`).
  - `.each` → `for...of`; `.toArray()` → the native array directly.
  - `.animate()` / Velocity → the **Web Animations API** (`el.animate(...)`),
    gated on `prefers-reduced-motion`.
- **`.data()` is gone** (see below).
- **Garnish `Drag` interop:** the parent-owned `$items`, `$draggee`, and
  `helpers` are already native `Element[]`/`HTMLElement[]`, so the `$(...)`
  wrappers the first port put around them were removed — they're used as native
  arrays/elements directly. FLD's own `$insertion`/`$caboose` are native too
  (`HTMLElement | null` / `HTMLElement[]`).

## How `.data()` was replaced

- **Object back-references** (legacy `$.data` cache) → module-level `WeakMap`s in
  `support.ts`, mirroring how `@craftcms/garnish` itself replaced `$.data`:
  `fldTabData` (`fld-tab`), `fldElementData` (`fld-element`), `hudData` (`hud`,
  keyed on `hud.$hud`), `cvdData` (`cvd`). The drag midpoints
  (`$item.data('midpoint')`) use a `WeakMap` local to `drags.ts`.
- **Plain `data-*` reads** (`uid`, `id`, `attribute`, `default-handle`, `type`,
  `ui-label`, `library`, `value`) → `element.dataset.*`.
- **JSON `data-*` reads** (`config`, `preview-options`, `thumb-options`) →
  `JSON.parse(element.dataset.…)` (jQuery's implicit JSON-parse made explicit).

## What deliberately stays jQuery (Craft seams)

Craft itself is still jQuery, so at these boundaries we call the Craft API and
immediately unwrap to native (`Craft.ui.x(...)[0]`, `slideout.$container[0]`) or
pass a `$(nativeEl)` wrapper in where Craft requires one. jQuery is confined to
the single call:

- **`Craft.Grid`** on `.fld-tabs` — instantiated and fed via `$(...)`
  (`tabGrid.addItems/removeItems`). _TODO:_ a later pass can replace it with CSS
  Grid (out of scope here).
- **`Craft.Listbox`**, **`Craft.SlidePicker`**, **`Craft.SortableCheckboxSelect`**,
  **`Craft.Slideout` / `Craft.CpScreenSlideout`** — instantiated at the seam;
  their jQuery `$container` is unwrapped with `[0]` for native DOM work.
- The **`.disclosureMenu()` jQuery plugin** + `.data('disclosureMenu')`, and
  `.data('sortableCheckboxSelect')` — Craft stores/reads these via jQuery.
- **`slideout.$container.serialize()`** (jQuery-only form serialize) and the
  `Craft.ui.addErrorsToField`/`clearErrorsFromField` error path, which require
  jQuery fields.
- Plain (non-jQuery) Craft calls — `Craft.t`, `Craft.sendActionRequest`,
  `Craft.cp.*`, `Craft.escapeHtml`, `Craft.uuid`, `Craft.appendHead/BodyHtml`,
  `Craft.initUiElements` — are left as-is.

The module still assigns `window.Craft.FieldLayoutDesigner` (plus `.Tab`,
`.Element`, `.CardViewDesigner`, `.BaseDrag`, `.TabDrag`, `.ElementDrag`) so the
existing PHP `registerJs("new Craft.FieldLayoutDesigner('#id', settings)")` keeps
working unchanged.

## Porting note: the "fade in place" drag branch

The legacy "fade the draggee in place" branch of `BaseDrag.onDragStop` relied on
`Drag._showDraggee` (private in modern Garnish) and the jQuery Velocity plugin.
It's replicated FLD-locally via `_showDraggeeFallback()` + a small `_fade()`
helper built on the **Web Animations API** (reduced-motion gated), with no change
to the `@craftcms/garnish` package.

## Files

- `FieldLayoutDesigner.ts` — the main designer class (+ static `defaults` /
  `createSlideout`).
- `Tab.ts` — a tab.
- `Element.ts` — a layout element (field / UI element).
- `CardViewDesigner.ts` — the card view designer.
- `drags.ts` — `BaseDrag` / `TabDrag` / `ElementDrag`.
- `support.ts` — the `.data()`-replacement WeakMaps and the `htmlToElement` /
  `firstFocusableInSiblings` DOM helpers.
- `index.ts` — wires the sub-classes onto the constructor and assigns
  `window.Craft.FieldLayoutDesigner`. Imported from `resources/js/cp.ts`.
- `types.ts` — settings + config types.

## Deferred

Behavioral/browser verification (rendering the FLD in the CP and exercising
tabs, drag-sort, the field-library HUD, settings HUDs, and the card view
designer) is left to manual testing.
