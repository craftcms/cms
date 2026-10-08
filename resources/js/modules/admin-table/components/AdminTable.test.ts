import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {useCraftTable} from '@/modules/admin-table/craftTable';
import AdminTable from './AdminTable.vue';

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({props: {readOnly: false}}),
}));
beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});
let teardown: (() => void) | undefined;
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

it.each([undefined, true, false])(
  'renders settings rows with footer option %s',
  (showFooter) => {
    const onPaginationChange = vi.fn();
    const table = useCraftTable({
      data: [{id: 1, name: 'Settings record'}],
      columns: [
        {accessorKey: 'name', header: 'Name', cell: ({getValue}) => getValue()},
      ],
      state: {pagination: {pageIndex: 0, pageSize: 50}},
      manualPagination: true,
      rowCount: 151,
      onPaginationChange,
    });
    const host = document.createElement('div');
    document.body.append(host);
    const app = createApp({
      render: () =>
        h(AdminTable, {
          table,
          from: 1,
          to: 50,
          total: 151,
          showFooter,
        } as never),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(host);
    teardown = () => app.unmount();
    expect(host.querySelector('tbody')?.textContent).toContain(
      'Settings record'
    );
    if (showFooter === false) {
      expect(host.querySelector('.admin-table__footer')).toBeNull();
      expect(onPaginationChange).not.toHaveBeenCalled();
      return;
    }
    expect(host.querySelector('.admin-table__footer')).not.toBeNull();
    Array.from(host.querySelectorAll('craft-icon'))
      .find(
        (icon) =>
          (icon as HTMLElement & {name: string}).name === 'chevron-right'
      )!
      .closest('craft-button')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    expect(onPaginationChange).toHaveBeenCalledOnce();
    const update = onPaginationChange.mock.calls[0]![0];
    expect(update({pageIndex: 0, pageSize: 50})).toEqual({
      pageIndex: 1,
      pageSize: 50,
    });
  }
);

function mountSelectionTable(selectable: boolean) {
  const table = useCraftTable({
    data: [
      {id: 1, name: 'First', url: '/first'},
      {id: 2, name: 'Second', url: '/second'},
      {id: 3, name: 'Third', url: '/third'},
    ],
    columns: [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: ({getValue, row}) =>
          h('a', {href: row.original.url}, getValue() as string),
      },
    ],
    getRowId: (row) => String(row.id),
  });
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () => h(AdminTable, {table, selectable} as never),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(host);
  teardown = () => app.unmount();
  const rows = () => Array.from(host.querySelectorAll('tbody tr'));
  const selectedIds = () =>
    table.getSelectedRowModel().rows.map((row) => row.original.id);

  return {host, rows, selectedIds};
}

it('renders no selection checkboxes unless selectable', () => {
  const {host} = mountSelectionTable(false);

  expect(host.querySelectorAll('craft-checkbox')).toHaveLength(0);
});

it('renders a select-all checkbox and one labelled checkbox per row', () => {
  const {host} = mountSelectionTable(true);

  expect(host.querySelectorAll('thead craft-checkbox')).toHaveLength(1);
  expect(host.querySelectorAll('tbody craft-checkbox')).toHaveLength(3);
  expect(host.querySelector('tbody craft-checkbox label')?.textContent).toBe(
    'Select First'
  );
});

it('toggles a row when its body is clicked and marks it selected', async () => {
  const {rows, selectedIds} = mountSelectionTable(true);

  rows()[1]!
    .querySelector('td:last-child')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await nextTick();

  expect(selectedIds()).toEqual([2]);
  expect(rows()[1]!.classList.contains('sel')).toBe(true);

  rows()[1]!
    .querySelector('td:last-child')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await nextTick();

  expect(selectedIds()).toEqual([]);
  expect(rows()[1]!.classList.contains('sel')).toBe(false);
});

it('extends the selection across a range on shift-click', async () => {
  const {rows, selectedIds} = mountSelectionTable(true);

  rows()[0]!
    .querySelector('td:last-child')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  rows()[2]!
    .querySelector('td:last-child')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true, shiftKey: true}));
  await nextTick();

  expect(selectedIds()).toEqual([1, 2, 3]);
});

it('leaves clicks on links inside a row to the link', () => {
  const {rows, selectedIds} = mountSelectionTable(true);
  const link = rows()[0]!.querySelector('a')!;
  link.addEventListener('click', (event) => event.preventDefault());

  link.dispatchEvent(new MouseEvent('click', {bubbles: true}));

  expect(selectedIds()).toEqual([]);
});

it('ignores row clicks when not selectable', () => {
  const {rows, selectedIds} = mountSelectionTable(false);

  rows()[0]!
    .querySelector('td:last-child')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));

  expect(selectedIds()).toEqual([]);
  expect(rows()[0]!.hasAttribute('tabindex')).toBe(false);
});

it('toggles a focused row with space and extends with shift+arrow', async () => {
  const {rows, selectedIds} = mountSelectionTable(true);

  rows()[0]!.dispatchEvent(
    new KeyboardEvent('keydown', {key: ' ', bubbles: true})
  );
  rows()[0]!.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'ArrowDown',
      shiftKey: true,
      bubbles: true,
    })
  );
  await nextTick();

  expect(selectedIds()).toEqual([1, 2]);
});
