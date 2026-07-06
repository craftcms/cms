import {computed, onMounted, type Ref} from 'vue';
import {
  createIndexVisitor,
  type ElementIndexRoute,
} from '@/modules/elements/composables/useElementIndexVisits';
import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';

// Element index rows are dynamic attribute maps, so the column helper is typed
// against an open record (matching the page's `Element` type).
type Row = Record<any, any>;

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
 *   user override (`viewState.columns[sourceKey]`)
 *     → the source's own `tableAttributes` (custom sources)
 *     → the element-type defaults.
 * A single pinned column (e.g. the element's title) is always rendered first
 * and excluded from toggling/reordering.
 */
export function useElementIndexColumns(
  props: ElementIndexColumnsContext,
  viewState: Ref<ViewState>,
  pinned: PinnedColumn,
  route: ElementIndexRoute
) {
  const visitor = createIndexVisitor(route);

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

  // Available columns ordered by the persisted (per-source) order; unknown/new
  // columns fall to the end in their natural order. Each column is keyed by its
  // `value` (e.g. `field:{uuid}`), which matches the row data's attribute keys.
  const orderedColumns = computed<Array<[string, {label: string}]>>(() => {
    const order = sourceColumnState.value?.order ?? [];
    return props.tableColumns
      .map((column): [string, {label: string}] => [column.value, column])
      .sort(([a], [b]) => {
        const ai = order.indexOf(a);
        const bi = order.indexOf(b);
        if (ai === -1 && bi === -1) return 0;
        if (ai === -1) return 1;
        if (bi === -1) return -1;
        return ai - bi;
      });
  });

  // The selected columns, in display order (drives both the table columns and
  // the table's column order).
  const visibleOrderedColumns = computed(() =>
    orderedColumns.value.filter(([key]) => visibleKeys.value.includes(key))
  );

  const columnHelper = createCraftColumnHelper<Row>();

  const columns = computed(() => [
    columnHelper.html(pinned.key, {header: pinned.label}),
    ...visibleOrderedColumns.value.map(([key, value]) =>
      columnHelper.html(key, {header: value.label})
    ),
  ]);

  // The table's column order, kept in sync with `columns` (pinned first).
  const columnOrder = computed(() => [
    pinned.key,
    ...visibleOrderedColumns.value.map(([key]) => key),
  ]);

  // Options for the toggle/sort controls: the pinned column (always on) first,
  // then every available column in display order.
  const columnOptions = computed(() => [
    {
      label: pinned.label,
      value: pinned.key,
      disabled: true,
      checked: true,
    },
    ...orderedColumns.value.map(([key, value]) => ({
      label: value.label,
      value: key,
    })),
  ]);

  // Merge a patch into the active source's view state (and persist it).
  function patchSourceColumnState(patch: {
    visible?: Array<string>;
    order?: Array<string>;
  }) {
    const sources = {...(viewState.value.sources ?? {})};
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
  // the newly visible columns' data from the server.
  const tableColumns = computed<Array<string>>({
    get: () => visibleKeys.value,
    set: (value) => {
      patchSourceColumnState({visible: value});
      refetchWithColumns(value);
    },
  });

  // On load, if the user has a persisted column selection that differs from
  // what the server rendered by default, restore it into the URL (without
  // adding a history entry) so the rows include those columns' data.
  onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const hasColumnsInUrl = [...params.keys()].some(
      (key) => key === 'columns' || key.startsWith('columns[')
    );
    const persisted = sourceColumnState.value?.visible;

    if (hasColumnsInUrl || !persisted?.length) {
      return;
    }

    if (JSON.stringify(persisted) === JSON.stringify(defaultVisible.value)) {
      return;
    }

    refetchWithColumns(persisted, {replace: true});
  });

  function reorder(options: Array<{value: string}>) {
    // Persist the new column order for this source; the pinned column always
    // stays first, so it's excluded from the stored order.
    patchSourceColumnState({
      order: options
        .map((option) => option.value)
        .filter((value) => value !== pinned.key),
    });
  }

  return {columns, columnOrder, columnOptions, reorder, tableColumns};
}
