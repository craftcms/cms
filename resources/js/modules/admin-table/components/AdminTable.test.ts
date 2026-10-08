import {createApp, h} from 'vue';
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

it('renders settings rows and drives caller pagination without an element payload', () => {
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
    render: () => h(AdminTable, {table, from: 1, to: 50, total: 151} as never),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(host);
  teardown = () => app.unmount();
  expect(host.querySelector('tbody')?.textContent).toContain('Settings record');
  Array.from(host.querySelectorAll('craft-icon'))
    .find(
      (icon) => (icon as HTMLElement & {name: string}).name === 'chevron-right'
    )!
    .closest('craft-button')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  expect(onPaginationChange).toHaveBeenCalledOnce();
  const update = onPaginationChange.mock.calls[0]![0];
  expect(update({pageIndex: 0, pageSize: 50})).toEqual({
    pageIndex: 1,
    pageSize: 50,
  });
});
