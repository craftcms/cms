import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {useCraftTable} from '@/common/table/craftTable';
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

it('connects selectable rows and their labelled controls to the table selection', async () => {
  const {host, rows, selectedIds} = mountSelectionTable(true);

  expect(host.querySelectorAll('thead craft-checkbox')).toHaveLength(1);
  expect(host.querySelectorAll('tbody craft-checkbox')).toHaveLength(3);
  expect(host.querySelector('tbody craft-checkbox label')?.textContent).toBe(
    'Select First'
  );

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

it('omits selection controls and ignores row clicks when not selectable', () => {
  const {host, rows, selectedIds} = mountSelectionTable(false);

  expect(host.querySelectorAll('craft-checkbox')).toHaveLength(0);

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

it.each([true, false])(
  'only pads the table and flushes its row edges when padded (%s)',
  (padded) => {
    const table = useCraftTable({
      data: [{id: 1, name: 'Settings record'}],
      columns: [
        {accessorKey: 'name', header: 'Name', cell: ({getValue}) => getValue()},
      ],
    });
    const host = document.createElement('div');
    document.body.append(host);
    const app = createApp({
      render: () => h(AdminTable, {table, padded} as never),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(host);
    teardown = () => app.unmount();

    expect(
      host
        .querySelector('.admin-table')
        ?.classList.contains('admin-table--padded')
    ).toBe(padded);
    expect(
      host.querySelector('table')?.classList.contains('cp-table--flush')
    ).toBe(padded);
  }
);
