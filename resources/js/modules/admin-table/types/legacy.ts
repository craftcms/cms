/**
 * The `Craft.VueAdminTable` settings surface, as Craft 5 plugins pass it.
 *
 * Only the options the shim renders itself are typed in detail; the rest are
 * listed in `DELEGATED_OPTIONS` so a table using any of them falls through to
 * the legacy implementation rather than rendering a silently lesser table.
 *
 * @see resources/js/legacy-admin-table.ts
 */

/** A row, as the server serialized it. */
export type LegacyAdminTableRow = Record<string, any>;

export interface LegacyAdminTableColumn {
  /** The row key this column reads. */
  name: string;
  title?: string;
  /**
   * Transforms the cell value. Craft 5 rendered the result as HTML, so the
   * shim does too.
   */
  callback?: (value: any, row: LegacyAdminTableRow) => string;
  /** Accepted and ignored: see `LegacyAdminTableSettings`. */
  titleClass?: string;
  dataClass?: string;
  sortField?: string;
}

export interface LegacyAdminTableSettings {
  /** Selector or element to mount into. */
  container?: string | HTMLElement | null;
  columns?: Array<LegacyAdminTableColumn>;
  tableData?: Array<LegacyAdminTableRow>;
  emptyMessage?: string;

  deleteAction?: string | null;
  deleteConfirmationMessage?: string | null;
  deleteSuccessMessage?: string | null;
  deleteFailMessage?: string | null;

  /**
   * Accepted so they don't send a table to the legacy implementation, but not
   * acted on — these described the old table's chrome, which the new one
   * handles its own way: `padded`, `fullPage`, `fullPane`, `itemLabels`,
   * `perPage`, `noSearchResults`, and a column's `titleClass`/`dataClass`/
   * `sortField`.
   */
  padded?: boolean;
  fullPage?: boolean;
  fullPane?: boolean;
  itemLabels?: {singular?: string; plural?: string};
  perPage?: number;
  noSearchResults?: string;

  [option: string]: unknown;
}

/**
 * Options the shim has no equivalent for. A table configured with any of them
 * is handed to the legacy implementation untouched.
 *
 * Server-backed data, search, reordering, row selection and bulk actions are
 * all whole features rather than cosmetic differences, and the row-level event
 * hooks hand out Vue 2 internals that nothing here can stand in for.
 */
export const DELEGATED_OPTIONS = [
  'tableDataEndpoint',
  'search',
  'searchParams',
  'searchClear',
  'searchPlaceholder',
  'reorderAction',
  'paginatedReorderAction',
  'moveToPageAction',
  'checkboxes',
  'checkboxStatus',
  'allowMultipleSelections',
  'allowMultipleDeletions',
  'minItems',
  'actions',
  'footerActions',
  'buttons',
  'beforeDelete',
  'deleteCallback',
  'onCellClicked',
  'onCellDoubleClicked',
  'onData',
  'onLoaded',
  'onLoading',
  'onPagination',
  'onQueryParams',
  'onRowClicked',
  'onRowDoubleClicked',
  'onSelect',
] as const satisfies ReadonlyArray<string>;
