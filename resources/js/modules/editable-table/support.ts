import type {EditableTable} from './EditableTable';
import type {Row} from './EditableTable';

/**
 * Native replacements for the legacy jQuery `$.data()` back-references the
 * editable table used to stash object instances on DOM nodes, mirroring how
 * `@craftcms/garnish` (and the FLD port) replaced `$.data` with module-level
 * WeakMaps keyed by the element.
 *
 * The legacy code stored `$table.data('editable-table', this)` and
 * `$tr.data('editable-table-row', this)`; those become these two maps. Plain
 * `data-*` attribute reads (e.g. `data-id`) are NOT here — they stay on
 * `element.dataset` / `getAttribute`.
 */

/** Legacy `$table.data('editable-table', table)` / `.data('editable-table')`. */
export const editableTableData = new WeakMap<Element, EditableTable>();

/** Legacy `$tr.data('editable-table-row', row)` / `.data('editable-table-row')`. */
export const editableTableRowData = new WeakMap<Element, Row>();
