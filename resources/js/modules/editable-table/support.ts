import type {EditableTable, Row} from './EditableTable';

/**
 * WeakMaps replacing the legacy jQuery `$.data()` back-references that stashed
 * instances on DOM nodes (matching the `@craftcms/garnish`/FLD pattern). Only the
 * object back-references move here; plain `data-*` reads stay on `element.dataset`.
 */

/** Legacy `$table.data('editable-table', table)` / `.data('editable-table')`. */
export const editableTableData = new WeakMap<Element, EditableTable>();

/** Legacy `$tr.data('editable-table-row', row)` / `.data('editable-table-row')`. */
export const editableTableRowData = new WeakMap<Element, Row>();
