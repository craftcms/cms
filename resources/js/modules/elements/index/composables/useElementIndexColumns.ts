import {computed, type Ref} from 'vue';
import {
  queryHasParam,
  type IndexQueryParams,
  type IndexVisitor,
  type IndexRestore,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';
import type {ElementIndexRow} from '@/modules/elements/index/composables/useContentIndexData';

interface ElementIndexColumnsContext {
  /**
   * Columns available for the current source: `{label, value, align}` per
   * column, where `align` is `end` for quantities.
   */
  tableColumns: Array<TableColumn>;
  /** The active source; a custom source may carry its own `tableAttributes`. */
  source?: SourceItem | null;
  /** Element-type default columns, used when the source defines none. */
  defaultTableColumns?: Array<string>;
  /** `tableColumns` lists the columns the rows were rendered with. */
  viewState?: {tableColumns?: Array<string>};
}

interface TableColumn {
  label: string;
  value: string;
  align?: 'start' | 'end';
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
  pinned: PinnedColumn | null,
  visitor: IndexVisitor
) {
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
  const visibleKeys = computed<Array<string>>(() =>
    (sourceColumnState.value?.visible ?? defaultVisible.value).filter(
      (key) => key !== pinned?.key
    )
  );

  // Available columns by key (e.g. `field:{uuid}`, matching row attribute keys).
  const columnsByKey = computed(
    () => new Map(props.tableColumns.map((column) => [column.value, column]))
  );

  // The visible columns, in the user's order (`visible` is an ordered array).
  // Keys persisted for since-removed columns are silently skipped.
  const visibleOrderedColumns = computed<Array<TableColumn>>(() =>
    visibleKeys.value
      .map((key) => columnsByKey.value.get(key))
      .filter((column) => column !== undefined)
  );

  const columnHelper = createCraftColumnHelper<ElementIndexRow>();

  const columns = computed(() => [
    ...(pinned ? [columnHelper.html(pinned.key, {header: pinned.label})] : []),
    ...visibleOrderedColumns.value.map((column) =>
      // Read the key as-is: Content Block keys such as `contentBlock:X.field:Y`
      // contain dots, which TanStack would treat as a nested path.
      columnHelper.html((row) => row[column.value], {
        id: column.value,
        header: column.label,
        meta: {align: column.align ?? 'start'},
      })
    ),
  ]);

  const columnOrder = computed(() => [
    ...(pinned ? [pinned.key] : []),
    ...visibleOrderedColumns.value.map((column) => column.value),
  ]);

  // Options for the toggle/reorder controls, in canonical presentation order:
  // the pinned column (always on), the visible columns in the user's order,
  // then the remaining columns alphabetically.
  const columnOptions = computed(() => {
    const visible = new Set(visibleKeys.value);

    return [
      ...(pinned
        ? [
            {
              label: pinned.label,
              value: pinned.key,
              disabled: true,
              checked: true,
            },
          ]
        : []),
      ...visibleOrderedColumns.value.map((column) => ({
        label: column.label,
        value: column.value,
      })),
      ...props.tableColumns
        .filter(
          (column) => column.value !== pinned?.key && !visible.has(column.value)
        )
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

  // Column visits also reload `viewState`, whose `tableColumns` names the
  // columns the rows were rendered with; `restore()` reads it from the page's
  // history state.
  const COLUMN_PROPS = ['data', 'viewState'];

  // The server only renders the columns it's asked for, so any change to the
  // visible set refetches the rows with the new `columns` selection. Ordering
  // is purely presentational and stays client-side.
  function refetchWithColumns(
    visible: Array<string>,
    options: {replace?: boolean} = {}
  ) {
    visitor.merge(
      {columns: visible},
      {only: COLUMN_PROPS, replace: options.replace ?? false}
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
        ...value.filter(
          (key) => key !== pinned?.key && !visibleKeys.value.includes(key)
        ),
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
  // the columns the server rendered, restore it so the rows include those
  // columns' data. The page folds this into one mount-time restore visit
  // alongside the view-mode/sort restores (see `useElementIndex`), so they
  // can't interrupt each other. `query` holds the params the link named.
  function restore(
    query: IndexQueryParams = visitor.currentQuery()
  ): IndexRestore | null {
    const persisted = sourceColumnState.value?.visible?.filter((key) =>
      columnsByKey.value.has(key)
    );
    const rendered = props.viewState?.tableColumns ?? defaultVisible.value;
    // Rows have the same data whatever the columns' order, and reordering
    // doesn't touch the URL, so only which columns show is compared.
    const matches =
      persisted?.length === rendered.length &&
      rendered.every((key) => persisted.includes(key));

    // Columns the link names win, and become the source's, so the columns
    // shown are the ones the rows have data for.
    if (queryHasParam(query, 'columns')) {
      if (props.viewState?.tableColumns && !matches) {
        patchSourceColumnState({visible: props.viewState.tableColumns});
      }

      return null;
    }

    // With nothing saved, the source's default columns show, so rows rendered
    // for columns the URL holds, such as from an older session copy, are
    // requested again without them.
    if (!persisted?.length) {
      return queryHasParam(visitor.currentQuery(), 'columns')
        ? {params: {columns: null}, only: COLUMN_PROPS}
        : null;
    }

    if (matches) {
      return null;
    }

    return {params: {columns: persisted}, only: COLUMN_PROPS};
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
        .filter((value) => value !== pinned?.key && visible.has(value)),
    });
  }

  /** Whether the visible columns, and their order, are the source's default. */
  const isDefault = computed(() => {
    const defaults = defaultVisible.value.filter((key) => key !== pinned?.key);

    return (
      visibleKeys.value.length === defaults.length &&
      visibleKeys.value.every((key, index) => key === defaults[index])
    );
  });

  /**
   * Forgets the source's column selection, so the server renders its default
   * columns. Folded into one visit with the other View menu settings (see
   * `useElementIndex`).
   */
  function resetToDefault(): IndexRestore | null {
    if (isDefault.value) {
      return null;
    }

    const sources = {...viewState.value.sources};
    sources[sourceKey.value] = {
      ...sources[sourceKey.value],
      visible: undefined,
    };
    viewState.value.sources = sources;

    return {params: {columns: null}, only: COLUMN_PROPS};
  }

  return {
    columns,
    columnOrder,
    columnOptions,
    reorder,
    tableColumns,
    restore,
    isDefault,
    resetToDefault,
  };
}
