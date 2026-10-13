import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import DataTable from './DataTable.vue';
import {useCraftTable} from '@/common/table/craftTable';
import {useTableRowSelection} from '@/common/composables/useTableRowSelection';

let teardown: (() => void) | undefined;
beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

function mountTable(
  options: {
    readOnly?: boolean;
    interactionsDisabled?: boolean;
    excludeSecond?: boolean;
    rowBehavior?: {onClick: () => boolean};
  } = {}
) {
  const table = useCraftTable({
    data: [
      {id: 1, name: 'First'},
      {id: 2, name: 'Second'},
      {id: 3, name: 'Third'},
    ],
    columns: [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: ({getValue}) => h('a', {href: '#record'}, getValue() as string),
      },
    ],
    getRowId: (row) => String(row.id),
    enableRowSelection: (row) =>
      !options.excludeSecond || row.original.id !== 2,
  });
  const selection = useTableRowSelection(table, {
    selectable: true,
    readOnly: false,
  });
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApp({
    render: () =>
      h(DataTable, {
        table,
        selection,
        readOnly: options.readOnly,
        rowBehavior: options.rowBehavior,
        interactionsDisabled: options.interactionsDisabled,
      } as never),
  });
  app.mount(root);
  teardown = () => app.unmount();
  return {root, table, selection};
}

it('uses the external selection anchor when a range starts in another view', async () => {
  const {root, table, selection} = mountTable();
  selection.selection.handleClick(1, new MouseEvent('click'));
  const lastRow = root.querySelectorAll<HTMLTableRowElement>('tbody tr')[2]!;
  lastRow.dispatchEvent(
    new MouseEvent('click', {bubbles: true, shiftKey: true})
  );
  await nextTick();
  expect(
    table.getSelectedRowModel().rows.map((row) => row.original.id)
  ).toEqual([1, 2, 3]);
  expect(root.querySelectorAll('tbody tr.sel')).toHaveLength(3);
});

it.each(['readOnly', 'interactionsDisabled'] as const)(
  'preserves selection when checkbox events arrive while %s',
  async (option) => {
    const {root, selection, table} = mountTable({[option]: true});
    selection.selection.select(1, true);
    await nextTick();
    const all = root.querySelector<HTMLElement & {checked: boolean}>(
      'thead craft-checkbox'
    )!;
    all.checked = true;
    all.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
    const row = root.querySelector<HTMLElement & {checked: boolean}>(
      'tbody craft-checkbox'
    )!;
    row.checked = false;
    row.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
    root.querySelector('tbody tr')!.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: ' ',
        bubbles: true,
        cancelable: true,
      })
    );
    await nextTick();

    expect(
      table.getSelectedRowModel().rows.map((row) => row.original.id)
    ).toEqual([1]);
    expect(root.querySelectorAll('tbody craft-checkbox')).toHaveLength(3);
    expect(
      Array.from(
        root.querySelectorAll<HTMLElement & {disabled: boolean}>(
          'craft-checkbox'
        )
      ).every((control) => control.disabled)
    ).toBe(true);
  }
);

it('selects eligible rows through the shared select-all checkbox', async () => {
  const {root, selection} = mountTable({excludeSecond: true});
  const all = root.querySelector<HTMLElement & {checked: boolean}>(
    'thead craft-checkbox'
  )!;
  all.checked = true;
  all.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await nextTick();

  expect(selection.selectedIds.value).toEqual([1, 3]);
  expect(root.querySelectorAll('tbody tr.sel')).toHaveLength(2);
  expect(
    root.querySelectorAll<HTMLElement & {disabled: boolean}>(
      'tbody craft-checkbox'
    )[1]!.disabled
  ).toBe(true);
});

it('leaves the link default action intact when custom row behavior handles its click', async () => {
  const {root, selection} = mountTable({rowBehavior: {onClick: () => true}});
  const event = new MouseEvent('click', {bubbles: true, cancelable: true});
  root.querySelector('tbody a')!.dispatchEvent(event);
  await nextTick();

  expect(event.defaultPrevented).toBe(false);
  expect(selection.selectedIds.value).toEqual([]);
});

it('toggles a focused row with Space and extends selection and focus with Shift+Arrow', async () => {
  const {root, selection} = mountTable();
  const rows = root.querySelectorAll<HTMLTableRowElement>('tbody tr');
  rows[0]!.focus();
  rows[0]!.dispatchEvent(
    new KeyboardEvent('keydown', {key: ' ', bubbles: true, cancelable: true})
  );
  await nextTick();
  expect(selection.selectedIds.value).toEqual([1]);

  rows[0]!.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'ArrowDown',
      shiftKey: true,
      bubbles: true,
      cancelable: true,
    })
  );
  await nextTick();

  expect(selection.selectedIds.value).toEqual([1, 2]);
  expect(document.activeElement).toBe(rows[1]);
});

it('leaves ordinary link clicks to the link without selecting its row', async () => {
  const {root, selection} = mountTable();
  const event = new MouseEvent('click', {bubbles: true, cancelable: true});
  root.querySelector('tbody a')!.dispatchEvent(event);
  await nextTick();

  expect(event.defaultPrevented).toBe(false);
  expect(selection.selectedIds.value).toEqual([]);
});
