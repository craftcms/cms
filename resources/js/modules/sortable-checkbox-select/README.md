# Sortable Checkbox Select

A deprecated shim for the legacy `Craft.SortableCheckboxSelect`. Checkbox selects
are now `<craft-checkbox-select>` elements from `@craftcms/ui`, with independent
`sortable` and `presentation="add-menu"` options, and an options API for changing
them at runtime.

`new Craft.SortableCheckboxSelect($container)` upgrades a legacy
`.cp-checkbox-select` container to `<craft-checkbox-select sortable
presentation="add-menu">`, keeping its attributes and items, and re-triggers the
element's `craft-order-change` as the garnish `sortChange` event. It also sets the
`$container.data('sortableCheckboxSelect')` back-reference older code reads.

Nothing in core uses it; `Craft.ui.createCheckboxSelect()` builds the element
directly. Remove it once plugins have moved to the element.
