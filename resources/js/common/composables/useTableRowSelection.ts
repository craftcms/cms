import {computed, type MaybeRefOrGetter, toValue} from 'vue';
import type {Row, Table} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/modules/admin-table/craftTable';
import {isInteractiveClick} from '@/common/utils/dom';
import {
  type SelectableId,
  useSelectable,
} from '@/common/composables/useSelectable';

export interface TableRowSelectionOptions {
  selectable: MaybeRefOrGetter<boolean>;
  readOnly: MaybeRefOrGetter<boolean>;
}

/**
 * Row selection for a TanStack table, in the table's own `Row`-shaped terms.
 *
 * The anchor/range mechanics live in {@link useSelectable}; this adds the parts
 * that are specific to tables — translating rows to ids, TanStack's
 * select-all. Selection state stays in the
 * table rather than being mirrored here, so the checkboxes, the row model and
 * this composable can never disagree.
 */
export function useTableRowSelection<TData extends Record<string, any>>(
  table: MaybeRefOrGetter<Table<CraftTableFeatures, TData>>,
  options: TableRowSelectionOptions
) {
  const readOnly = computed(() => toValue(options.readOnly));
  const selectable = computed(() => toValue(options.selectable));

  const rows = (): Array<Row<CraftTableFeatures, TData>> =>
    toValue(table).getRowModel().rows;
  const rowFor = (
    id: SelectableId
  ): Row<CraftTableFeatures, TData> | undefined =>
    rows().find((row) => row.original.id === id);

  const selection = useSelectable({
    ids: () => rows().map((row) => row.original.id),
    enabled: selectable,
    readOnly,
    // Tables select through checkboxes, so a plain click adds to the
    // selection rather than collapsing it to the clicked row.
    click: 'toggle',
    canSelect: (id) => rowFor(id)?.getCanSelect() ?? false,
    store: {
      isSelected: (id) => rowFor(id)?.getIsSelected() ?? false,
      setSelected: (id, selected) => rowFor(id)?.toggleSelected(selected),
      selectedIds: () =>
        toValue(table)
          .getSelectedRowModel()
          .rows.map((row) => row.original.id),
      clear: () => toValue(table).resetRowSelection(),
    },
  });

  const {anchorIndex, hasSelection, selectedIds} = selection;

  function clearSelection() {
    selection.clear();
  }

  // craft-checkbox (Lion) fires `model-value-changed` on programmatic `.checked`
  // updates too, so only act when the incoming value actually differs.
  function onToggleAllSelected(checked: boolean) {
    if (readOnly.value) return;
    const t = toValue(table);
    if (checked !== t.getIsAllRowsSelected()) {
      t.toggleAllRowsSelected(checked);
    }
  }

  function selectRow(
    row: Row<CraftTableFeatures, TData>,
    {checked, shiftKey = false}: {checked: boolean; shiftKey?: boolean}
  ) {
    selection.setChecked(row.original.id, checked, {shiftKey});
  }

  function toggleRow(row: Row<CraftTableFeatures, TData>) {
    if (readOnly.value) return;
    selection.toggle(row.original.id);
  }

  // A click anywhere on a selectable row/card body toggles that row, unless it
  // landed on an interactive control. Reuses selectRow so a shift-click extends
  // the range from the anchor exactly like shift-clicking the checkbox does.
  function selectRowFromEvent(
    row: Row<CraftTableFeatures, TData> | undefined,
    event: MouseEvent
  ) {
    if (!selectable.value || readOnly.value || !row) return;
    if (!row.getCanSelect()) return;
    if (isInteractiveClick(event)) return;

    selectRow(row, {
      checked: !row.getIsSelected(),
      shiftKey: event.shiftKey,
    });

    // A shift-click also extends the browser's text selection; drop it so the
    // range select doesn't leave stray highlighted text behind.
    if (event.shiftKey) {
      window.getSelection?.()?.removeAllRanges();
    }
  }

  function extendSelectionTo(row: Row<CraftTableFeatures, TData>) {
    if (readOnly.value) return;
    selection.extendTo(row.original.id);
  }

  return {
    // The shared primitive, for handing to list bodies that take a
    // `selection` prop (ElementCards, ElementThumbs, …).
    selection,
    selectedIds,
    hasSelection,
    readOnly,
    anchorIndex,
    clearSelection,
    onToggleAllSelected,
    selectRow,
    selectRowFromEvent,
    toggleRow,
    extendSelectionTo,
  };
}

export type TableRowSelection<TData extends Record<string, any>> = ReturnType<
  typeof useTableRowSelection<TData>
>;
