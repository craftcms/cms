import {describe, expect, it} from 'vitest';
import {ref} from 'vue';
import {useElementIndexSelection} from './useElementIndexSelection';

// Minimal TanStack-table stand-in: rows own their selected state.
function makeRow(id: number, selected = false) {
  let sel = selected;
  return {
    id: String(id),
    original: {id},
    getIsSelected: () => sel,
    toggleSelected: (v?: boolean) => {
      sel = v ?? !sel;
    },
  };
}

function makeTable(rows: ReturnType<typeof makeRow>[]) {
  let allToggled: boolean | null = null;
  return {
    getRowModel: () => ({rows}),
    getSelectedRowModel: () => ({rows: rows.filter((r) => r.getIsSelected())}),
    getIsAllRowsSelected: () => rows.every((r) => r.getIsSelected()),
    toggleAllRowsSelected: (v: boolean) => {
      allToggled = v;
      rows.forEach((r) => r.toggleSelected(v));
    },
    _allToggled: () => allToggled,
    resetRowSelection: () => rows.forEach((r) => r.toggleSelected(false)),
  } as any;
}

const opts = (over = {}) => ({selectable: true, readOnly: false, actions: [], ...over});

describe('useElementIndexSelection', () => {
  it('toggles a single row and sets the anchor', () => {
    const rows = [makeRow(1), makeRow(2), makeRow(3)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[1]! as any, {checked: true});

    expect(rows[1]!.getIsSelected()).toBe(true);
    expect(s.anchorIndex.value).toBe(1);
    expect(s.selectedIds.value).toEqual([2]);
    expect(s.hasSelection.value).toBe(true);
  });

  it('shift-clicking selects the inclusive range from the anchor', () => {
    const rows = [makeRow(1), makeRow(2), makeRow(3), makeRow(4)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0]! as any, {checked: true}); // anchor = 0
    s.selectRow(rows[2]! as any, {checked: true, shiftKey: true});

    expect(rows.map((r) => r.getIsSelected())).toEqual([true, true, true, false]);
  });

  it('ignores a programmatic change where checked already matches (no shift)', () => {
    const rows = [makeRow(1, true)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0]! as any, {checked: true}); // already selected → no-op, no anchor
    expect(s.anchorIndex.value).toBe(null);
  });

  it('does nothing when read-only', () => {
    const rows = [makeRow(1)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({readOnly: true}));

    s.selectRow(rows[0]! as any, {checked: true});
    s.onToggleAllSelected(true);

    expect(rows[0]!.getIsSelected()).toBe(false);
  });

  it('computes bulk-action visibility from selectable + actions + selection', () => {
    const rows = [makeRow(1)];
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
