import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useCraftTable} from './craftTable';
import {createCraftColumnHelper} from './helpers/createCraftColumnHelper';
import {useServerSort} from './composables/useServerSort';

interface TestRow {
  id: number;
  title: string;
}

const data: TestRow[] = [{id: 1, title: 'First'}];
const columnHelper = createCraftColumnHelper<TestRow>();
const columns = columnHelper.columns([
  columnHelper.accessor('title', {header: 'Title'}),
]);

beforeEach(() => {
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
