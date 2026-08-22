import {describe, expect, it} from 'vite-plus/test';
import {ref, type Ref} from 'vue';
import type {Row, Table} from '@tanstack/vue-table';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {
  useElementIndexSelection,
  type ElementIndexSelectionOptions,
} from './useElementIndexSelection';

interface TestElement {
  id: number;
}

function rowAt(rows: Array<Row<TestElement>>, index: number): Row<TestElement> {
  const row = rows[index];
  if (!row) throw new Error(`Expected row ${index}.`);

  return row;
}

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
): Row<TestElement> {
  const rowId = String(id);
  if (selected) {
    selection.value = {...selection.value, [rowId]: true};
  }
  // SAFETY: This focused Row fixture implements every member read by the selection composable.
  return {
    id: rowId,
    original: {id},
    getCanSelect: () => true,
    getIsSelected: () => !!selection.value[rowId],
    toggleSelected: (v?: boolean) => {
      const next = v ?? !selection.value[rowId];
      selection.value = {...selection.value, [rowId]: next};
    },
  } as Row<TestElement>;
}

function makeTable(rows: Array<Row<TestElement>>): Table<TestElement> {
  // SAFETY: This focused Table fixture implements every member read by the selection composable.
  return {
    getRowModel: () => ({rows}),
    getSelectedRowModel: () => ({rows: rows.filter((r) => r.getIsSelected())}),
    getIsAllRowsSelected: () => rows.every((r) => r.getIsSelected()),
    toggleAllRowsSelected: (v: boolean) => {
      rows.forEach((r) => r.toggleSelected(v));
    },
    resetRowSelection: () => rows.forEach((r) => r.toggleSelected(false)),
  } as Table<TestElement>;
}

const opts = (
  over: Partial<ElementIndexSelectionOptions> = {}
): ElementIndexSelectionOptions => ({
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

    s.selectRow(rowAt(rows, 1), {checked: true});

    expect(rowAt(rows, 1).getIsSelected()).toBe(true);
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

    s.selectRow(rowAt(rows, 0), {checked: true}); // anchor = 0
    s.selectRow(rowAt(rows, 2), {checked: true, shiftKey: true});

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

    s.selectRow(rowAt(rows, 0), {checked: true}); // already selected → no-op, no anchor
    expect(s.anchorIndex.value).toBe(null);
  });

  it('does nothing when read-only', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({readOnly: true}));

    s.selectRow(rowAt(rows, 0), {checked: true});
    s.onToggleAllSelected(true);

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
  });

  // A stand-in for the row/card element the `@click` listener is bound to;
  // `tabindex="0"` mirrors the real selectable row so we also cover that the
  // row's own focusability is not mistaken for an interactive control.
  const rowEl = document.createElement('tr');
  rowEl.tabIndex = 0;

  // Builds a click-like event from real DOM nodes. `path` is ordered deepest
  // first (as `composedPath()` is) and the row element is appended so the walk
  // terminates there, exactly like a real click bubbling to the row listener.
  function clickEvent(path: Element | Element[] = [], shiftKey = false) {
    const nodes = Array.isArray(path) ? path : [path];
    const event = new MouseEvent('click', {shiftKey});
    Object.defineProperties(event, {
      currentTarget: {value: rowEl},
      composedPath: {value: () => [...nodes, rowEl]},
    });

    return event;
  }

  it('toggles the row when the click lands on the row body', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection), makeRow(2, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    // Clicking a plain cell — and even the row itself — is not interactive.
    s.selectRowFromEvent(
      rowAt(rows, 1),
      clickEvent(document.createElement('td'))
    );

    expect(rowAt(rows, 1).getIsSelected()).toBe(true);
    expect(s.anchorIndex.value).toBe(1);
  });

  it('ignores a row click that lands on a link', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    const link = document.createElement('a');
    link.setAttribute('href', '/edit/1');
    const label = document.createElement('span'); // a click on a child of the link
    link.append(label);

    s.selectRowFromEvent(rowAt(rows, 0), clickEvent([label, link]));

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
    expect(s.anchorIndex.value).toBe(null);
  });

  it('ignores a checkbox click via the focusable control in its shadow path', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    // The `craft-checkbox` host is not focusable, but composedPath surfaces the
    // real focusable control from inside its shadow root — so the row defers.
    const host = document.createElement('craft-checkbox');
    const innerInput = document.createElement('input');

    s.selectRowFromEvent(rowAt(rows, 0), clickEvent([innerInput, host]));

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
  });

  it('does nothing on a row click when read-only', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({readOnly: true}));

    s.selectRowFromEvent(rowAt(rows, 0), clickEvent());

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
  });

  it('does nothing on a row click when the index is not selectable', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({selectable: false}));

    s.selectRowFromEvent(rowAt(rows, 0), clickEvent());

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
  });

  it('respects a row that cannot be selected', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    const row = rowAt(rows, 0);
    row.getCanSelect = () => false;

    s.selectRowFromEvent(row, clickEvent());

    expect(rowAt(rows, 0).getIsSelected()).toBe(false);
  });

  it('shift-clicking a row body extends the range from the anchor', () => {
    const selection = makeSelection();
    const rows = [
      makeRow(1, selection),
      makeRow(2, selection),
      makeRow(3, selection),
    ];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRowFromEvent(rowAt(rows, 0), clickEvent());
    s.selectRowFromEvent(rowAt(rows, 2), clickEvent([], true));

    expect(rows.map((r) => r.getIsSelected())).toEqual([true, true, true]);
  });

  it('computes bulk-action visibility from selectable + actions + selection', () => {
    const selection = makeSelection();
    const rows = [makeRow(1, selection)];
    const table = makeTable(rows);
    const actions = ref<Array<BulkActionItem>>([
      {key: 'delete', label: 'Delete'},
    ]);
    const s = useElementIndexSelection(table, opts({actions}));

    expect(s.hasBulkActions.value).toBe(true);
    expect(s.showBulkActions.value).toBe(true);
    expect(s.bulkActionsActive.value).toBe(false);

    s.selectRow(rowAt(rows, 0), {checked: true});
    expect(s.bulkActionsActive.value).toBe(true);
  });
});
