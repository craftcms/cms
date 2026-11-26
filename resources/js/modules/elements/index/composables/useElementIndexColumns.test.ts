import {describe, expect, it, vi} from 'vite-plus/test';
import {ref} from 'vue';
import {useCraftTable} from '@/common/table/craftTable';
import type {ViewState} from '@/modules/elements/types/view-state';
import {useElementIndexColumns} from './useElementIndexColumns';

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

  it.each([
    [{}, {params: {columns: ['postDate']}, only: ['data']}],
    [{'columns[0]': 'postDate'}, null],
    [{columns: ['postDate']}, null],
  ])(
    'restores saved columns unless the query %o names them',
    (query, expected) => {
      const {restore} = useElementIndexColumns(
        {
          tableColumns: [
            {label: 'Post Date', value: 'postDate'},
            {label: 'Author', value: 'author'},
          ],
          defaultTableColumns: ['author'],
        },
        ref<ViewState>({
          showHeaderColumn: true,
          static: false,
          sources: {'*': {visible: ['postDate']}},
        }),
        null,
        {...visitor, currentQuery: () => query}
      );

      expect(restore()).toEqual(expected);
    }
  );
});
