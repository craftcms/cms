import {describe, expect, it, vi} from 'vite-plus/test';
import {ref} from 'vue';
import {useCraftTable} from '@/common/table/craftTable';
import type {ViewState} from '@/modules/elements/types/view-state';
import {useElementIndexColumns} from './useElementIndexColumns';
import type {IndexQueryParams} from './useElementIndexVisits';

vi.mock('@inertiajs/vue3', () => ({router: {visit: vi.fn()}}));

const visitor = {merge: vi.fn(), visit: vi.fn(), currentQuery: () => ({})};

describe('useElementIndexColumns', () => {
  // Content Block columns are keyed like `contentBlock:X.field:Y`; the dot
  // must not be read as a path into the row.
  it('reads column keys that contain dots as-is', () => {
    const key = 'contentBlock:block-uid.field:field-uid';
    const {columns} = useElementIndexColumns(
      {
        tableColumns: [{label: 'Headline', value: key}],
        defaultTableColumns: [key],
      },
      ref<ViewState>({showHeaderColumn: true, static: false}),
      null,
      visitor
    );
    const table = useCraftTable({
      data: [{id: 1, label: 'Entry', [key]: 'Nested headline'}],
      columns: columns.value,
    });

    expect(table.getRowModel().rows[0]!.getValue(key)).toBe('Nested headline');
  });

  function columnsFor(query: IndexQueryParams, rendered?: string[]) {
    const viewState = ref<ViewState>({
      showHeaderColumn: true,
      static: false,
      sources: {'*': {visible: ['postDate']}},
    });
    const {restore} = useElementIndexColumns(
      {
        tableColumns: [
          {label: 'Post Date', value: 'postDate'},
          {label: 'Author', value: 'author'},
        ],
        defaultTableColumns: ['author'],
        viewState: rendered ? {tableColumns: rendered} : undefined,
      },
      viewState,
      null,
      {...visitor, currentQuery: () => query}
    );

    return {restore, viewState};
  }

  it.each([
    [{}, {params: {columns: ['postDate']}, only: ['data']}],
    [{'columns[0]': 'postDate'}, null],
    [{columns: ['postDate']}, null],
  ])(
    'restores saved columns unless the query %o names them',
    (query, expected) => {
      expect(columnsFor(query).restore()).toEqual(expected);
    }
  );

  // The server can render the saved columns from the session's copy, in which
  // case the rows already have their data.
  it('leaves rows rendered with the saved columns alone', () => {
    expect(columnsFor({}, ['postDate']).restore()).toBeNull();
  });

  // Columns the server filled in from an older session copy aren't the link's
  // choice, so the saved ones still win.
  it('requests the saved columns over ones filled in from the session', () => {
    const {restore} = columnsFor({'columns[0]': 'author'}, ['author']);

    expect(restore({})).toEqual({
      params: {columns: ['postDate']},
      only: ['data'],
    });
  });

  // Otherwise the table would show the saved columns, which the rows have no
  // data for.
  it('saves the columns a link names', () => {
    const {restore, viewState} = columnsFor({'columns[0]': 'author'}, [
      'author',
    ]);

    expect(restore()).toBeNull();
    expect(viewState.value.sources?.['*']?.visible).toEqual(['author']);
  });
});
