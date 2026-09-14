import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';

/**
 * A non-page index — the element selector modal — keeps its query in a visitor
 * rather than the URL. Reading `window.location.search` instead took the host
 * page's params and dropped the index's own (the chosen source).
 */
beforeEach(() => {
  // Both composables read the CP's page-param name off the legacy global.
  vi.stubGlobal('Craft', {pageTrigger: 'page'});
});

describe('currentQuery override', () => {
  it('is what sort builds on, not the page URL', () => {
    const onChange = vi.fn();
    const {onSortingChange} = useServerSort({
      initialState: [],
      currentQuery: () => ({source: 'section:news'}),
      onChange,
    });

    onSortingChange([{id: 'title', desc: false}]);

    expect(onChange.mock.calls[0]![0].query).toMatchObject({
      source: 'section:news',
    });
  });

  it('is what pagination builds on, not the page URL', () => {
    const onChange = vi.fn();
    const {onPaginationChange} = useServerPagination({
      initialState: {current_page: 1, per_page: 50, total: 100} as any,
      currentQuery: () => ({source: 'section:news'}),
      onChange,
    });

    onPaginationChange({pageIndex: 1, pageSize: 50});

    expect(onChange.mock.calls[0]![0].query).toMatchObject({
      source: 'section:news',
    });
  });

  it('falls back to the page URL when none is given', () => {
    const onChange = vi.fn();
    const {onSortingChange} = useServerSort({initialState: [], onChange});

    onSortingChange([{id: 'title', desc: false}]);

    expect(onChange).toHaveBeenCalled();
  });
});
