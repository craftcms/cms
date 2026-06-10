import {computed, type Ref} from 'vue';
import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
import type {ViewState} from '@/modules/elements/types/view-state';

// Element index rows are dynamic attribute maps, so the column helper is typed
// against an open record (matching the page's `Element` type).
type Row = Record<any, any>;

interface ElementIndexColumnsContext {
  tableColumns: Record<string, {label: string}>;
}

interface PinnedColumn {
  /** Column key that is always shown first and can't be toggled or reordered. */
  key: string;
  label: string;
}

/**
 * Derives everything column-shaped for an element index from the available
 * columns and the persisted view state: the TanStack column definitions, the
 * table's column order, the toggle/sort options, and a reorder handler that
 * persists the new order. A single pinned column (e.g. the element's title) is
 * always rendered first and excluded from reordering.
 */
export function useElementIndexColumns(
  props: ElementIndexColumnsContext,
  viewState: Ref<ViewState>,
  pinned: PinnedColumn
) {
  // Available columns ordered by the persisted column order (unknown/new
  // columns fall to the end in their natural order).
  const orderedColumns = computed<Array<[string, {label: string}]>>(() => {
    const order = viewState.value.columnOrder ?? [];
    return [...Object.entries(props.tableColumns)].sort(([a], [b]) => {
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
    orderedColumns.value.filter(([key]) =>
      viewState.value.tableColumns?.includes(key)
    )
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

  function reorder(options: Array<{value: string}>) {
    // Persist the new column order; the pinned column always stays first, so
    // it's excluded from the stored order.
    viewState.value.columnOrder = options
      .map((option) => option.value)
      .filter((value) => value !== pinned.key);
  }

  return {columns, columnOrder, columnOptions, reorder};
}
