import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {nextTick, reactive} from 'vue';
import type {PaginationData, SortItem} from '@/common/types';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './createCraftColumnHelper';
import {useServerSort} from './useServerSort';

interface TestRow {
  id: number;
  title: string;
}

const data: TestRow[] = [{id: 1, title: 'First'}];
const columnHelper = createCraftColumnHelper<TestRow>();
const columns = columnHelper.columns([
  columnHelper.accessor('title', {header: 'Title'}),
]);

const visit = vi.hoisted(() => vi.fn());
vi.mock('@inertiajs/vue3', () => ({router: {visit}}));

function pagination(page: number): PaginationData {
  return {
    total: 120,
    per_page: 50,
    current_page: page,
    last_page: 3,
    next_page_url: null,
    prev_page_url: null,
    from: (page - 1) * 50 + 1,
    to: Math.min(page * 50, 120),
  };
}

beforeEach(() => {
  visit.mockClear();
  // useServerSort reads the CP's page-param name off the legacy global.
  vi.stubGlobal('Craft', {pageTrigger: 'page'});
});

describe('useCraftTable', () => {
  it('leaves columns unsortable, as nothing would sort them', () => {
    const table = useCraftTable({data, columns});

    expect(table.getColumn('title')?.getCanSort()).toBe(false);
  });

  it('makes columns sortable for a table sorted on the server', () => {
    const {sortingConfig} = useServerSort({
      initialState: [],
      onChange: () => {},
      currentQuery: () => ({}),
    });
    const table = useCraftTable({data, columns, ...sortingConfig});

    expect(table.getColumn('title')?.getCanSort()).toBe(true);
  });
});

describe('useCraftTable with inertia', () => {
  function inertiaTable() {
    const props = reactive({
      rows: data,
      pagination: pagination(1),
      sort: [{field: 'title', direction: 'asc'}] as SortItem[],
    });
    const table = useCraftTable({
      get data() {
        return props.rows;
      },
      columns,
      inertia: {
        url: '/admin/widgets',
        pagination: () => props.pagination,
        sort: () => props.sort,
        dataProp: 'rows',
      },
    });

    return {props, table};
  }

  it('visits the page URL for page and sort changes, reloading rows, pagination and sort together', () => {
    const {table} = inertiaTable();

    table.setPageIndex(1);
    table.getColumn('title')?.toggleSorting(true);

    const only = ['rows', 'pagination', 'sort'];
    expect(visit).toHaveBeenNthCalledWith(
      1,
      '/admin/widgets',
      expect.objectContaining({
        data: expect.objectContaining({page: 2, per_page: 50}),
        only,
      })
    );
    expect(visit).toHaveBeenNthCalledWith(
      2,
      '/admin/widgets',
      expect.objectContaining({
        data: expect.objectContaining({
          page: 1,
          sort: {0: {field: 'title', direction: 'desc'}},
        }),
        only,
      })
    );
  });

  it('follows its props once a visit reloads them', async () => {
    const {props, table} = inertiaTable();

    props.rows = [{id: 2, title: 'Second'}];
    props.pagination = pagination(3);
    props.sort = [{field: 'title', direction: 'desc'}];
    await nextTick();

    expect(table.getRowModel().rows.map((row) => row.original.id)).toEqual([2]);
    expect(table.atoms.pagination.get().pageIndex).toBe(2);
    expect(table.getRowCount()).toBe(120);
    expect(table.getColumn('title')?.getIsSorted()).toBe('desc');
  });
});
