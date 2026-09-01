import {computed, type MaybeRefOrGetter, toValue} from 'vue';
import type {Row, Table} from '@tanstack/vue-table';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {isInteractiveClick} from '@/common/utils/dom';
import {
  type SelectableId,
  useSelectable,
} from '@/common/composables/useSelectable';

export interface ElementIndexSelectionOptions {
  selectable: MaybeRefOrGetter<boolean>;
  readOnly: MaybeRefOrGetter<boolean>;
  actions: MaybeRefOrGetter<Array<BulkActionItem> | null | undefined>;
}

/**
 * The element index's selection, in the index's own `Row`-shaped terms.
 *
 * The anchor/range mechanics live in {@link useSelectable}; this adds the parts
 * that are specific to the index — translating rows to ids, TanStack's
 * select-all, and the bulk-action visibility flags. Selection state stays in the
 * table rather than being mirrored here, so the checkboxes, the row model and
 * this composable can never disagree.
 */
export function useElementIndexSelection(
  table: MaybeRefOrGetter<Table<any>>,
  options: ElementIndexSelectionOptions
) {
  const readOnly = computed(() => toValue(options.readOnly));
  const selectable = computed(() => toValue(options.selectable));

  const rows = (): Array<Row<any>> => toValue(table).getRowModel().rows;
  const rowFor = (id: SelectableId): Row<any> | undefined =>
    rows().find((row) => row.original.id === id);

  const selection = useSelectable({
    ids: () => rows().map((row) => row.original.id),
    enabled: selectable,
    readOnly,
    // The index selects through checkboxes, so a plain click adds to the
    // selection rather than collapsing it to the clicked row.
    click: 'toggle',
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

  const hasBulkActions = computed(
    () => (toValue(options.actions)?.length ?? 0) > 0
  );
  const showBulkActions = computed(
    () => selectable.value && hasBulkActions.value
  );
  const bulkActionsActive = computed(
    () => showBulkActions.value && hasSelection.value
  );

  function clearSelection() {
    toValue(table).resetRowSelection();
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
    row: Row<any>,
    {checked, shiftKey = false}: {checked: boolean; shiftKey?: boolean}
  ) {
    selection.setChecked(row.original.id, checked, {shiftKey});
  }

  function toggleRow(row: Row<any>) {
    if (readOnly.value) return;
    selection.toggle(row.original.id);
  }

  // A click anywhere on a selectable row/card body toggles that row, unless it
  // landed on an interactive control. Reuses selectRow so a shift-click extends
  // the range from the anchor exactly like shift-clicking the checkbox does.
  function selectRowFromEvent(row: Row<any> | undefined, event: MouseEvent) {
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

  function extendSelectionTo(row: Row<any>) {
    if (readOnly.value) return;
    selection.extendTo(row.original.id);
  }

  return {
    // The shared primitive, for handing to list bodies that take a
    // `selection` prop (ElementCards, ElementThumbs, …).
    selection,
    selectedIds,
    hasSelection,
    hasBulkActions,
    showBulkActions,
    bulkActionsActive,
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
