# Sortable Checkbox Select

A TypeScript port of the legacy jQuery `Craft.SortableCheckboxSelect`
(`packages/craftcms-legacy/cp/src/js/SortableCheckboxSelect.js`) onto the modern
**`@craftcms/garnish`** `Base`. It powers the `forms.sortableCheckboxSelect`
macro / `_includes/forms/checkboxSelect.twig` and the
`Craft.ui.createSortableCheckboxSelect` factory, and is consumed by the modern
[Card View Designer](../field-layout-designer/card-view-designer.ts).

## What changed

- **Class system.** `Garnish.Base.extend({…})` → `class extends Base`;
  `init()` is still the setup method but is invoked from a `constructor` via the
  `new.target` leaf guard (the same construction contract as the EditableTable /
  GeneratedFieldsTable ports); `Craft.SortableCheckboxSelect.Item` → the exported
  `Item` class.
- **`.data()` → WeakMap (mirrored).** The object back-reference the legacy code
  stashed on the container — `$container.data('sortableCheckboxSelect', this)` —
  is now also kept in a module-level `WeakMap` in `support.ts`
  (`sortableCheckboxSelectData`). The class still sets the jQuery `.data()` too —
  see *Removability* — and modern consumers read the WeakMap.
- **ESM/bundle wiring.** Imported from `resources/js/cp.ts`; the legacy
  `SortableCheckboxSelect.js` is removed from the legacy `Craft.js` bundle.

## What deliberately stays jQuery (Garnish / Craft seams)

Like the EditableTable port, this class is largely an orchestrator of
still-jQuery widgets, so jQuery (`$`) and the legacy `Garnish` global survive at
those seams and the public `$container` / `$item` stay jQuery:

- `Garnish.DragSort` (+ `Garnish.Y_AXIS`) for drag-reordering, `Garnish.DisclosureMenu`
  (`addItem` / `showItem` / `hideItem` / `on('show')` / `destroy`) for the
  per-item action menu, `Craft.ui.icon`, `Craft.t`, `Craft.hasMousePointerEvents`
  — accessed via the legacy globals (`declare const Garnish/Craft/$`).
- The item emits the legacy jQuery DOM events `checked` / `unchecked` /
  `movedUp` / `movedDown` on `$item` (kept as `$item.trigger(...)` — they have no
  in-repo listeners but are part of the public event contract). The `sortChange`
  event is `Base` pub/sub (`this.trigger('sortChange')`), consumed by both the
  modern CVD and the legacy `BaseElementIndex` via `.on('sortChange')`.

## Construction & global

`window.Craft.SortableCheckboxSelect` is assigned the **plain modern ES class**
(plus `.Item`) — **no `compatify`**, because nothing subclasses it via the legacy
`Garnish.Base.extend()`. So `new Craft.SortableCheckboxSelect($container)` (the
`Craft.ui` factory, `checkboxSelect.twig`, `BaseElementIndex`) keeps working
unchanged.

The `<craft-sortable-checkbox-select>` custom element self-boots the class around
the `.cp-checkbox-select` it wraps and re-emits its `sortChange` as a bubbling DOM
event — how the Card View Designer consumes it without referencing the class.

## Removability of the legacy file

The legacy `SortableCheckboxSelect.js` is **removed** from the legacy bundle; its
global is provided here. The one remaining compat seam is the jQuery
`$container.data('sortableCheckboxSelect', this)` write: the still-legacy
`BaseElementIndex` reads the instance back that way
(`BaseElementIndex.js:4768`). Once `BaseElementIndex` (and the `Craft.ui`
factory) are themselves modernized, that jQuery `.data()` write can be dropped in
favor of the `support.ts` WeakMap alone.

## Files

- `sortable-checkbox-select.ts` — the `SortableCheckboxSelect` class and the
  `Item` class.
- `sortable-checkbox-select.ce.ts` — `<craft-sortable-checkbox-select>`, the
  self-booting custom element.
- `support.ts` — the `.data()`-mirror WeakMap.
- `index.ts` — assigns `window.Craft.SortableCheckboxSelect` (+ `.Item`) and
  registers `<craft-sortable-checkbox-select>`. Imported from `resources/js/cp.ts`.

## Deferred

Behavioral/browser verification (checking/unchecking items, drag-reorder, the
Move up/down menu, and the Card View Designer's library) is left to manual
testing.
