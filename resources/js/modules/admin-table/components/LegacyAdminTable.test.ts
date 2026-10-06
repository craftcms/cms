import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {Table} from '@tanstack/vue-table';

const captured: {table: Table<any> | null} = {table: null};

// Stands in for the real table so the assertions can be about what the shim
// derived — columns, rows, the delete cell — rather than how it's drawn.
vi.mock('@/modules/admin-table/components/AdminTable.vue', () => ({
  default: {
    name: 'AdminTable',
    props: ['table', 'from', 'to', 'total', 'layout', 'reorderable'],
    setup(props: any) {
      captured.table = props.table;

      return () => h('div', {class: 'admin-table-stub'});
    },
  },
}));

let app: App | null = null;

beforeEach(() => {
  captured.table = null;
  document.body.innerHTML = '';
  (window as any).Craft = {
    sendActionRequest: vi.fn().mockResolvedValue({data: {}}),
    cp: {displayNotice: vi.fn(), displayError: vi.fn()},
  };
});

afterEach(() => {
  app?.unmount();
  app = null;
  delete (window as any).Craft;
});

async function mount(props: Record<string, unknown>) {
  const LegacyAdminTable = (await import('./LegacyAdminTable.vue')).default;
  const host = document.createElement('div');
  document.body.append(host);

  app = createApp(LegacyAdminTable, props);
  app.mount(host);
  await new Promise((resolve) => setTimeout(resolve, 0));

  return host;
}

const columns = [
  {name: 'objects', title: 'Objects'},
  {
    name: 'status',
    title: 'Status',
    callback: (value: string) => `<b>${value}</b>`,
  },
];

it('builds a column per legacy column, in order', async () => {
  await mount({
    columns,
    tableData: [{id: 1, objects: '12', status: 'ok'}],
  });

  const ids = captured.table!.getAllColumns().map((column) => column.id);

  expect(ids).toEqual(['objects', 'status']);
});

it('renders a cell through its callback as HTML', async () => {
  await mount({
    columns,
    tableData: [{id: 1, objects: '12', status: 'ok'}],
  });

  const cells = captured.table!.getRowModel().rows[0]!.getAllCells();
  const status = cells.find((cell) => cell.column.id === 'status')!;

  // The callback's markup reaches the cell as HTML, not as escaped text.
  const rendered = (status.column.columnDef.cell as Function)({
    row: {original: {id: 1, objects: '12', status: 'ok'}},
  });

  expect(rendered.props.innerHTML).toBe('<b>ok</b>');
});

it('adds a delete column only when there is a delete action', async () => {
  await mount({columns, tableData: [{id: 1}]});
  expect(
    captured.table!.getAllColumns().map((column) => column.id)
  ).not.toContain('actions');

  app?.unmount();
  app = null;

  await mount({columns, tableData: [{id: 1}], deleteAction: 'plugin/delete'});
  expect(captured.table!.getAllColumns().map((column) => column.id)).toContain(
    'actions'
  );
});

/**
 * `_showDelete: false` is how the legacy table hid the button per row —
 * Shopify's sync utility uses it to protect an in-flight operation.
 */
it('honours a row’s _showDelete flag', async () => {
  await mount({
    columns,
    tableData: [
      {id: 1, _showDelete: true},
      {id: 2, _showDelete: false},
    ],
    deleteAction: 'plugin/delete',
  });

  const actions = captured
    .table!.getAllColumns()
    .find((column) => column.id === 'actions')!;
  const cell = actions.columnDef.cell as Function;

  const shown = cell({row: {original: {id: 1, _showDelete: true}}});
  const hidden = cell({row: {original: {id: 2, _showDelete: false}}});

  expect(shown.children).toHaveLength(1);
  expect(hidden.children).toHaveLength(0);
});

it('posts the delete and drops the row', async () => {
  await mount({
    columns,
    tableData: [{id: 7, objects: '1'}],
    deleteAction: 'plugin/sync/delete',
    deleteSuccessMessage: 'Sync deleted',
  });

  expect(captured.table!.getRowModel().rows).toHaveLength(1);

  const actions = captured
    .table!.getAllColumns()
    .find((column) => column.id === 'actions')!;
  const vnode = (actions.columnDef.cell as Function)({
    row: {original: captured.table!.getRowModel().rows[0]!.original},
  });

  await vnode.children[0].props.onClick();
  await new Promise((resolve) => setTimeout(resolve, 0));

  expect(window.Craft.sendActionRequest).toHaveBeenCalledWith(
    'POST',
    'plugin/sync/delete',
    {data: {id: 7}}
  );
  expect(window.Craft.cp!.displayNotice).toHaveBeenCalledWith('Sync deleted');
  expect(captured.table!.getRowModel().rows).toHaveLength(0);
});

it('reports a failed delete and keeps the row', async () => {
  (window as any).Craft.sendActionRequest = vi
    .fn()
    .mockRejectedValue(new Error('nope'));

  await mount({
    columns,
    tableData: [{id: 7}],
    deleteAction: 'plugin/sync/delete',
    deleteFailMessage: 'Sync could not be deleted',
  });

  const actions = captured
    .table!.getAllColumns()
    .find((column) => column.id === 'actions')!;
  const vnode = (actions.columnDef.cell as Function)({
    row: {original: captured.table!.getRowModel().rows[0]!.original},
  });

  await vnode.children[0].props.onClick();
  await new Promise((resolve) => setTimeout(resolve, 0));

  expect(window.Craft.cp!.displayError).toHaveBeenCalledWith(
    'Sync could not be deleted'
  );
  expect(captured.table!.getRowModel().rows).toHaveLength(1);
});
