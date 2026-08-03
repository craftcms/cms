import {describe, expect, it} from 'vite-plus/test';
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
        getSelectedRowModel: () => ({
            rows: rows.filter((r) => r.getIsSelected()),
        }),
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
        return {
            shiftKey,
            currentTarget: rowEl,
            composedPath: () => [...nodes, rowEl],
        } as unknown as MouseEvent;
    }

    it('toggles the row when the click lands on the row body', () => {
        const selection = makeSelection();
        const rows = [makeRow(1, selection), makeRow(2, selection)];
        const table = makeTable(rows);
        const s = useElementIndexSelection(table, opts());

        // Clicking a plain cell — and even the row itself — is not interactive.
        s.selectRowFromEvent(
            rows[1]! as any,
            clickEvent(document.createElement('td'))
        );

        expect(rows[1]!.getIsSelected()).toBe(true);
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

        s.selectRowFromEvent(rows[0]! as any, clickEvent([label, link]));

        expect(rows[0]!.getIsSelected()).toBe(false);
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

        s.selectRowFromEvent(rows[0]! as any, clickEvent([innerInput, host]));

        expect(rows[0]!.getIsSelected()).toBe(false);
    });

    it('does nothing on a row click when read-only', () => {
        const selection = makeSelection();
        const rows = [makeRow(1, selection)];
        const table = makeTable(rows);
        const s = useElementIndexSelection(table, opts({readOnly: true}));

        s.selectRowFromEvent(rows[0]! as any, clickEvent());

        expect(rows[0]!.getIsSelected()).toBe(false);
    });

    it('does nothing on a row click when the index is not selectable', () => {
        const selection = makeSelection();
        const rows = [makeRow(1, selection)];
        const table = makeTable(rows);
        const s = useElementIndexSelection(table, opts({selectable: false}));

        s.selectRowFromEvent(rows[0]! as any, clickEvent());

        expect(rows[0]!.getIsSelected()).toBe(false);
    });

    it('respects a row that cannot be selected', () => {
        const selection = makeSelection();
        const rows = [makeRow(1, selection)];
        const table = makeTable(rows);
        const s = useElementIndexSelection(table, opts());

        const row = {...rows[0]!, getCanSelect: () => false};

        s.selectRowFromEvent(row as any, clickEvent());

        expect(rows[0]!.getIsSelected()).toBe(false);
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

        s.selectRowFromEvent(rows[0]! as any, clickEvent());
        s.selectRowFromEvent(rows[2]! as any, clickEvent([], true));

        expect(rows.map((r) => r.getIsSelected())).toEqual([true, true, true]);
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
