# Editable Table

A TypeScript port of the legacy jQuery `Craft.EditableTable`
(`packages/craftcms-legacy/cp/src/js/EditableTable.js`) onto the modern
**`@craftcms/garnish`** `Base`. It powers the `forms.editableTable` /
`forms.editableTableField` macro (`_includes/forms/editableTable.twig`) and is
the base class for the [generated-fields](../generated-fields/) table.

## What changed

- **Class system.** `Garnish.Base.extend({…})` → `class extends Base`,
  `init()` is still the setup method but is now invoked from a `constructor`
  (see *Construction contract*), `Craft.EditableTable.Row` →
  the exported `Row` class.
- **`.data()` is gone.** The two object back-references the legacy code stashed
  on DOM nodes — `$table.data('editable-table')` and
  `$tr.data('editable-table-row')` — are now module-level `WeakMap`s in
  `support.ts` (`editableTableData`, `editableTableRowData`), mirroring how
  `@craftcms/garnish` itself replaced `$.data`. Plain `data-*` reads (`data-id`)
  stay on `getAttribute`/`dataset`.
- **TypeScript types.** `types.ts` defines `EditableTableSettings`,
  `EditableTableColumn(s)` (reusing `EditableTableCellType` from
  `@/common/types`).
- **Native event dispatch at the modern-`Base` seam.** `Base#addListener` now
  binds listeners natively, so the two places the legacy code used a jQuery
  `$el.trigger('input')` to fire one of its own listeners
  (`Row#init` priming + `importData`) dispatch a native
  `Event('input')` instead, and `handlePaste` reads `ev.clipboardData`
  directly rather than jQuery's `ev.originalEvent.clipboardData`.
- **ESM/bundle wiring.** Imported from `resources/js/cp.ts`; the legacy
  `EditableTable.js` is removed from the legacy `Craft.js` bundle.

## What deliberately stays jQuery (Craft / Garnish seams)

Unlike the FLD port, `EditableTable` is **almost entirely** an orchestrator of
still-jQuery Craft/Garnish widgets, so jQuery (`$`) and the legacy `Garnish`
global survive at those seams, and the public `$`-prefixed properties
(`$table`, `$tbody`, `$addRowBtn`, `Row#$tr`, `Row#$tds`, …) stay **real
jQuery** — external legacy code (the Table field's `TableFieldSettings` bundle)
reads them as jQuery (`.css`, `.find`, `.children`, `.replaceWith`,
`Garnish.getPostData($tbody)`) and passes jQuery `<tr>`s back into
`createRowObj`/`getRowObj`. The seams:

- `Craft.ui.*` (createCheckbox/Select/ColorInput/DateInput/TimeInput/
  Lightswitch/TextInput/IconPicker/icon), `Craft.DataTableSorter`,
  `Craft.HandleGenerator`, `Craft.t`/`Craft.uuid`/`Craft.trim`/
  `Craft.inArray`/`Craft.selectFullValue`/`Craft.filterNumberInputVal`/
  `Craft.hasMousePointerEvents`.
- `Garnish.NiceText` and `Garnish.DisclosureMenu` (not yet ported to the modern
  named API), `Garnish.RETURN_KEY`, `Garnish.isCtrlKeyPressed`,
  `Garnish.firstFocusableElement` — accessed via the legacy global.
- `static createRow()` is pure `Craft.ui.*` jQuery assembly and **returns a
  jQuery `<tr>`** — the contract `TableFieldSettings` depends on
  (`Craft.EditableTable.createRow(...).appendTo($tbody)`).

## Construction contract

Setup lives in `init()`, not the constructor, and the constructor only runs
`init()` for the **leaf** class (a `new.target` guard). This is what lets the
compat layer wrap the class so legacy subclasses keep working:

```js
// TableFieldSettings (unchanged, separate legacy bundle):
const ColumnTable = Craft.EditableTable.extend({
  init(fieldSettings, id, baseName, columns, settings) {
    this.fieldSettings = fieldSettings;
    this.base(id, baseName, columns, settings); // ← reshaped args
  },
  initialize() { return this.base() && …; },
});
ColumnTable.Row = Craft.EditableTable.Row.extend({ init(t, tr){ this.base(t, tr); … } });
```

The subclass `init` reshapes the constructor args before calling
`this.base(id, baseName, columns, settings)`. Because `compatify` forwards the
raw leaf args to `super()`, a constructor-based setup would receive the *shifted*
args and break — so the modern base **defers** setup to `init()` and the compat
trampoline (not our constructor) invokes it. Modern ES subclasses
(`class extends EditableTable`, e.g. `GeneratedFieldsTable`) call `this.init(...)`
from their own leaf constructor.

## Files

- `EditableTable.ts` — the `EditableTable` class (+ static `createRow` /
  `textualColTypes` / `defaults`) and the `Row` class.
- `types.ts` — settings + column types.
- `support.ts` — the `.data()`-replacement WeakMaps.
- `index.ts` — wraps both classes with `compatify` and assigns
  `window.Craft.EditableTable` (+ `.Row`). Imported from `resources/js/cp.ts`.

## Removability of the legacy file

`EditableTable.js` is removed from the legacy bundle; its global is now provided
here. The compat shim is what keeps the separate, still-legacy
`TableFieldSettings` bundle working against the modern class. When
`TableFieldSettings` is itself ported to `class extends EditableTable`, the
`compatify` wrapper in `index.ts` can be dropped in favor of a direct
`window.Craft.EditableTable = EditableTable` assignment.

## Deferred

Behavioral/browser verification (exercising add/delete/reorder, paste-import,
checkbox radio/toggle, the Table field settings screen, and the generated-fields
table) is left to manual testing.
