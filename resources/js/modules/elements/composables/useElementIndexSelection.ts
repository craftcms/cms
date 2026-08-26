import {computed, type MaybeRefOrGetter, ref, type Ref, toValue} from 'vue';
import type {Row, Table} from '@tanstack/vue-table';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {isInteractiveClick} from '@/common/utils/dom';

export interface ElementIndexSelectionOptions {
  selectable: MaybeRefOrGetter<boolean>;
  readOnly: MaybeRefOrGetter<boolean>;
  actions: MaybeRefOrGetter<Array<BulkActionItem> | null | undefined>;
}

export function useElementIndexSelection(
  table: MaybeRefOrGetter<Table<any>>,
  options: ElementIndexSelectionOptions
) {
  // The anchor is the last individually-toggled row; shift-click selects the
  // inclusive range between it and the clicked row in current row-model order.
  const anchorIndex: Ref<number | null> = ref(null);

  const readOnly = computed(() => toValue(options.readOnly));
  const selectable = computed(() => toValue(options.selectable));

  const selectedIds = computed<Array<string | number>>(() => {
    const t = toValue(table);
    const selectedRows = t.getSelectedRowModel().rows;
    return selectedRows.map((row) => row.original.id);
  });
  const hasSelection = computed(() => selectedIds.value.length > 0);
  const hasBulkActions = computed(
    () => (toValue(options.actions)?.length ?? 0) > 0
  );
  const showBulkActions = computed(
    () => toValue(options.selectable) && hasBulkActions.value
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

  function rowIndex(row: Row<any>): number {
    return toValue(table)
      .getRowModel()
      .rows.findIndex((r) => r.id === row.id);
  }

  function selectRow(
    row: Row<any>,
    {checked, shiftKey = false}: {checked: boolean; shiftKey?: boolean}
  ) {
    if (readOnly.value) return;
    const rows = toValue(table).getRowModel().rows;
    const index = rowIndex(row);

    if (shiftKey && anchorIndex.value !== null) {
      const [start, end] =
        anchorIndex.value <= index
          ? [anchorIndex.value, index]
          : [index, anchorIndex.value];
      for (let i = start; i <= end; i++) {
        rows[i]!.toggleSelected(checked);
      }
      return; // anchor is preserved across a range select
    }

    // Guard the programmatic re-fire: nothing to do if state already matches.
    if (checked === row.getIsSelected()) return;
    row.toggleSelected(checked);
    anchorIndex.value = index;
  }

  function toggleRow(row: Row<any>) {
    if (readOnly.value) return;
    row.toggleSelected();
    anchorIndex.value = rowIndex(row);
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
    const rows = toValue(table).getRowModel().rows;
    const index = rowIndex(row);
    const from = anchorIndex.value ?? index;
    const [start, end] = from <= index ? [from, index] : [index, from];
    for (let i = start; i <= end; i++) {
      rows[i]!.toggleSelected(true);
    }
  }

  return {
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
