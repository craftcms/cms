import {computed, type Ref} from 'vue';
import {
  createIndexVisitor,
  type IndexVisitor,
  type ElementIndexRoute,
  type IndexRestore,
} from '@/modules/elements/composables/useElementIndexVisits';
import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';
import type {ElementIndexRow} from '@/modules/elements/composables/useContentIndexData';

interface ElementIndexColumnsContext {
  /** Columns available for the current source: `{label, value}` per column. */
  tableColumns: Array<{label: string; value: string}>;
  /** The active source; a custom source may carry its own `tableAttributes`. */
  source?: SourceItem | null;
  /** Element-type default columns, used when the source defines none. */
  defaultTableColumns?: Array<string>;
}

interface PinnedColumn {
  /** Column key that is always shown first and can't be toggled or reordered. */
  key: string;
  label: string;
}

/**
 * Derives everything column-shaped for an element index from the available
 * columns and the persisted view state: the TanStack column definitions, the
 * table's column order, the toggle/sort options, and a reorder handler.
 *
 * Visible columns follow Craft's precedence, resolved per source:
 *   user override (`viewState.sources[sourceKey].visible`)
 *     → the source's own `tableAttributes` (custom sources)
 *     → the element-type defaults.
 *
 * The persisted `visible` array is the single source of order truth: the table
 * renders its columns in that order, checking a column appends it to the end,
 * and dragging in the View popover rewrites it. Unchecked columns have no
 * persisted order — they always present alphabetically.
 *
 * A single pinned column (e.g. the element's title) is always rendered first
 * and excluded from toggling/reordering.
 */
export function useElementIndexColumns(
  props: ElementIndexColumnsContext,
  viewState: Ref<ViewState>,
  pinned: PinnedColumn,
  route: ElementIndexRoute,
  /** Supplied by indexes that aren't a page — see {@link createIndexVisitor}. */
  indexVisitor?: IndexVisitor
) {
  const visitor = indexVisitor ?? createIndexVisitor(route);

  // Column state is stored per source; fall back to a shared bucket when there
  // is no resolved source (e.g. the implicit "all elements" view).
  const sourceKey = computed(() => props.source?.key ?? '*');

  const sourceColumnState = computed(
    () => viewState.value.sources?.[sourceKey.value]
  );

  // The source's configured columns (custom sources only).
  const sourceTableAttributes = computed<Array<string> | undefined>(() =>
    props.source && 'tableAttributes' in props.source
      ? props.source.tableAttributes
      : undefined
  );

  // Default visible set when the user hasn't customized this source: the
  // source's own `tableAttributes` win over the element-type defaults.
  const defaultVisible = computed<Array<string>>(
    () => sourceTableAttributes.value ?? props.defaultTableColumns ?? []
  );

  // Effective visible columns: the user's per-source selection if present,
  // otherwise the source/element-type default.
  const visibleKeys = computed<Array<string>>(
    () => sourceColumnState.value?.visible ?? defaultVisible.value
  );

  // Available columns by key (e.g. `field:{uuid}`, matching row attribute keys).
  const columnsByKey = computed(
    () => new Map(props.tableColumns.map((column) => [column.value, column]))
  );

  // The visible columns, in the user's order (`visible` is an ordered array).
  // Keys persisted for since-removed columns are silently skipped.
  const visibleOrderedColumns = computed<Array<{label: string; value: string}>>(
    () =>
      visibleKeys.value
        .map((key) => columnsByKey.value.get(key))
        .filter((column) => column !== undefined)
  );

  const columnHelper = createCraftColumnHelper<ElementIndexRow>();

  const columns = computed(() => [
    columnHelper.html(pinned.key, {header: pinned.label}),
    ...visibleOrderedColumns.value.map((column) =>
      columnHelper.html(column.value, {header: column.label})
    ),
  ]);

  // The table's column order, kept in sync with `columns` (pinned first).
  const columnOrder = computed(() => [
    pinned.key,
    ...visibleOrderedColumns.value.map((column) => column.value),
  ]);

  // Options for the toggle/reorder controls, in canonical presentation order:
  // the pinned column (always on), the visible columns in the user's order,
  // then the remaining columns alphabetically.
  const columnOptions = computed(() => {
    const visible = new Set(visibleKeys.value);

    return [
      {
        label: pinned.label,
        value: pinned.key,
        disabled: true,
        checked: true,
      },
      ...visibleOrderedColumns.value.map((column) => ({
        label: column.label,
        value: column.value,
      })),
      ...props.tableColumns
        .filter((column) => !visible.has(column.value))
        .sort((a, b) => a.label.localeCompare(b.label))
        .map((column) => ({label: column.label, value: column.value})),
    ];
  });

  // Merge a patch into the active source's view state (and persist it).
  function patchSourceColumnState(patch: {visible?: Array<string>}) {
    const sources = {...viewState.value.sources};
    sources[sourceKey.value] = {...sources[sourceKey.value], ...patch};
    viewState.value.sources = sources;
  }

  // The server only renders the columns it's asked for, so any change to the
  // visible set refetches the rows with the new `columns` selection. Ordering
  // is purely presentational and stays client-side.
  function refetchWithColumns(
    visible: Array<string>,
    options: {replace?: boolean} = {}
  ) {
    visitor.merge(
      {columns: visible},
      {only: ['data'], replace: options.replace ?? false}
    );
  }

  // Writable model for the toolbar's column toggles: reading yields the
  // effective visible keys; writing records a per-source override and pulls
  // the newly visible columns' data from the server. The checkbox group emits
  // values in its own order, so normalize: kept columns preserve their current
  // order, and newly checked ones append to the end.
  const tableColumns = computed<Array<string>>({
    get: () => visibleKeys.value,
    set: (value) => {
      const next = [
        ...visibleKeys.value.filter((key) => value.includes(key)),
        ...value.filter((key) => !visibleKeys.value.includes(key)),
      ];

      // The `craft-checkbox-group` emits its initial model value on mount, which
      // lands here as a no-op change. Ignore it so it doesn't fire a redundant
      // columns refetch — which, being viewMode-less, would otherwise interrupt
      // and clobber the mount-time view-mode/sort restore visits.
      if (
        next.length === visibleKeys.value.length &&
        next.every((key, index) => key === visibleKeys.value[index])
      ) {
        return;
      }

      patchSourceColumnState({visible: next});
      refetchWithColumns(next);
    },
  });

  // On load, if the user has a persisted column selection that differs from
  // what the server rendered by default, restore it so the rows include those
  // columns' data. The page folds this into one mount-time restore visit
  // alongside the view-mode/sort restores (see `useElementIndexPage`), so they
  // can't interrupt each other.
  function restore(): IndexRestore | null {
    const params = new URLSearchParams(window.location.search);
    const hasColumnsInUrl = [...params.keys()].some(
      (key) => key === 'columns' || key.startsWith('columns[')
    );
    const persisted = sourceColumnState.value?.visible;

    if (hasColumnsInUrl || !persisted?.length) {
      return null;
    }

    if (JSON.stringify(persisted) === JSON.stringify(defaultVisible.value)) {
      return null;
    }

    return {params: {columns: persisted}, only: ['data']};
  }

  function reorder(options: Array<{value: string}>) {
    // Dragging rewrites the visible columns' order to match what's displayed
    // (the pinned column always stays first, and unchecked columns carry no
    // persisted order). No refetch — the data is already loaded; only the
    // client-side column order changes.
    const visible = new Set(visibleKeys.value);

    patchSourceColumnState({
      visible: options
        .map((option) => option.value)
        .filter((value) => value !== pinned.key && visible.has(value)),
    });
  }

  return {columns, columnOrder, columnOptions, reorder, tableColumns, restore};
}
