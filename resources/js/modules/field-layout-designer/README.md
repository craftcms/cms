# Field Layout Designer (FLD)

A native, jQuery-free port of the legacy jQuery `Craft.FieldLayoutDesigner`
(`packages/craftcms-legacy/cp/src/js/FieldLayoutDesigner.js`) onto the modern
**`@craftcms/garnish`** package. FLD's own DOM/data work is plain DOM + WeakMaps;
jQuery (`$`) survives **only** at the boundary with Craft's still-jQuery widgets.

## What changed

- **Class system.** `Garnish.Base.extend({...})` / `Garnish.Drag.extend({...})` →
  `class extends Base` / `extends Drag`, `init()` → `constructor()`,
  `this.base(...)` → `super.method(...)`, `new Garnish.HUD(...)` → `new HUD(...)`.
  Garnish utilities/constants are named imports.
- **All of FLD's own jQuery DOM work is now native** — `document.createElement` /
  a `<template>`-based `htmlToElement()`, `classList`, `matches`,
  `querySelector(All)`/`closest`, `dataset`, and `el.animate()` (Web Animations
  API, reduced-motion gated) in place of Velocity. `.data()` is gone (see below).
- **Garnish `Drag` interop.** The parent-owned `$items`/`$draggee`/`helpers` are
  already native `Element[]`, so the first port's `$(...)` wrappers were dropped;
  FLD's own `$insertion`/`$caboose` are native (`HTMLElement | null` / `[]`).

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
these seams:

- **`Craft.Grid`** on `.fld-tabs` — instantiated and fed via `$(...)`. _TODO:_ a
  later pass can replace it with CSS Grid (out of scope here).
- **`Craft.Slideout` / `Craft.CpScreenSlideout`** — instantiated at the seam;
  their jQuery `$container` is unwrapped with `[0]`.
- **`slideout.$container.serialize()`** (jQuery-only form serialize) and the
  `Craft.ui.addErrorsToField`/`clearErrorsFromField` error path require jQuery
  fields.
- Plain (non-jQuery) Craft calls — `Craft.t`, `Craft.sendActionRequest`,
  `Craft.cp.*`, `Craft.escapeHtml`, `Craft.uuid`, `Craft.appendHead/BodyHtml`,
  `Craft.initUiElements` — are left as-is.

The module assigns `window.Craft.FieldLayoutDesigner` (plus `.Tab`, `.Element`,
`.CardViewDesigner`, `.BaseDrag`, `.TabDrag`, `.ElementDrag`) so the existing PHP
`registerJs("new Craft.FieldLayoutDesigner('#id', settings)")` keeps working, and
registers the self-booting `<craft-field-layout-designer>` element (the durable
fix for re-binding after an Inertia DOM swap). The Card View Designer consumes the
`<craft-listbox>` and `<craft-sortable-checkbox-select>` custom elements via their
DOM events rather than instantiating those Craft classes.

## Porting note: the "fade in place" drag branch

The legacy "fade the draggee in place" branch of `BaseDrag.onDragStop` relied on
`Drag._showDraggee` (private in modern Garnish) and the jQuery Velocity plugin.
It's replicated FLD-locally via `_showDraggeeFallback()` + a small `_fade()`
helper built on the **Web Animations API** (reduced-motion gated), with no change
to the `@craftcms/garnish` package.

## Files

- `field-layout-designer.ts` — the main designer class (+ static `defaults` /
  `createSlideout`).
- `tab.ts` — a tab.
- `element.ts` — a layout element (field / UI element).
- `card-view-designer.ts` — the card view designer.
- `field-layout-designer.ce.ts` — `<craft-field-layout-designer>`, the
  self-booting custom element wrapping `.layoutdesigner`.
- `drags.ts` — `BaseDrag` / `TabDrag` / `ElementDrag`.
- `support.ts` — the `.data()`-replacement WeakMaps and the `htmlToElement` /
  `firstFocusableInSiblings` DOM helpers.
- `index.ts` — wires the sub-classes onto the constructor, assigns
  `window.Craft.FieldLayoutDesigner`, and registers
  `<craft-field-layout-designer>`. Imported from `resources/js/cp.ts`.
- `types.ts` — settings + config types.

## Deferred

Behavioral/browser verification (rendering the FLD in the CP and exercising
tabs, drag-sort, the field-library HUD, settings HUDs, and the card view
designer) is left to manual testing.
