import {describe, expect, it} from 'vitest';
import {ref, type Ref} from 'vue';
import {useElementIndexSelection} from './useElementIndexSelection';

// Minimal TanStack-table stand-in: row-selection state lives in a single Vue
// ref (matching TanStack's `RowSelectionState`, a `Record<rowId, boolean>`),
// mirroring how the real table's `onRowSelectionChange` writes to a ref that
// `state.rowSelection` reads back. This lets plain `computed`s genuinely
// re-track, the same way they do in production.
function makeSelection(): Ref<Record<string, boolean>> {
  return ref({});
}

function makeRow(
  id: number,
  selection: Ref<Record<string, boolean>>,
  selected = false
) {
  const rowId = String(id);
  if (selected) {
    selection.value = {...selection.value, [rowId]: true};
  }
  return {
    id: rowId,
    original: {id},
    getIsSelected: () => !!selection.value[rowId],
    toggleSelected: (v?: boolean) => {
      const next = v ?? !selection.value[rowId];
      selection.value = {...selection.value, [rowId]: next};
    },
  };
}

function makeTable(rows: ReturnType<typeof makeRow>[]) {
  return {
    getRowModel: () => ({rows}),
    getSelectedRowModel: () => ({rows: rows.filter((r) => r.getIsSelected())}),
    getIsAllRowsSelected: () => rows.every((r) => r.getIsSelected()),
    toggleAllRowsSelected: (v: boolean) => {
      rows.forEach((r) => r.toggleSelected(v));
    },
    resetRowSelection: () => rows.forEach((r) => r.toggleSelected(false)),
  } as any;
}

const opts = (over = {}) => ({
  selectable: true,
  readOnly: false,
  actions: [],
  ...over,
});

describe('useElementIndexSelection', () => {
  it('toggles a single row and sets the anchor', () => {
    const selection = makeSelection();
    const rows = [
      makeRow(1, selection),
      makeRow(2, selection),
      makeRow(3, selection),
    ];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[1]! as any, {checked: true});

    expect(rows[1]!.getIsSelected()).toBe(true);
    expect(s.anchorIndex.value).toBe(1);
    expect(s.selectedIds.value).toEqual([2]);
    expect(s.hasSelection.value).toBe(true);
  });

  it('shift-clicking selects the inclusive range from the anchor', () => {
    const selection = makeSelection();
    const rows = [
      makeRow(1, selection),
      makeRow(2, selection),
      makeRow(3, selection),
      makeRow(4, selection),
    ];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0]! as any, {checked: true}); // anchor = 0
    s.selectRow(rows[2]! as any, {checked: true, shiftKey: true});

    expect(rows.map((r) => r.getIsSelected())).toEqual([
      true,
      true,
      true,
      false,
    ]);
  });

  it('ignores a programmatic change where checked already matches (no shift)', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection, true)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0]! as any, {checked: true}); // already selected → no-op, no anchor
    expect(s.anchorIndex.value).toBe(null);
  });

  it('does nothing when read-only', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({readOnly: true}));

    s.selectRow(rows[0]! as any, {checked: true});
    s.onToggleAllSelected(true);

    expect(rows[0]!.getIsSelected()).toBe(false);
  });

  it('computes bulk-action visibility from selectable + actions + selection', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const actions = ref<any[]>([{label: 'Delete'}]);
    const s = useElementIndexSelection(table, opts({actions}));

    expect(s.hasBulkActions.value).toBe(true);
    expect(s.showBulkActions.value).toBe(true);
    expect(s.bulkActionsActive.value).toBe(false);

    s.selectRow(rows[0]! as any, {checked: true});
    expect(s.bulkActionsActive.value).toBe(true);
  });
});
