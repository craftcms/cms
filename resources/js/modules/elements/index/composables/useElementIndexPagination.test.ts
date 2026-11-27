import {ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {PaginationData} from '@/common/types';
import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {ViewState} from '@/modules/elements/types/view-state';
import {useElementIndexPagination} from './useElementIndexPagination';

const pagination: PaginationData = {
  total: 120,
  per_page: 50,
  current_page: 1,
  last_page: 3,
  next_page_url: null,
  prev_page_url: null,
  from: 1,
  to: 50,
};

function visitor(query: IndexQueryParams = {}) {
  return {currentQuery: () => query, visit: vi.fn(), merge: vi.fn()};
}

describe('useElementIndexPagination', () => {
  beforeEach(() => vi.stubGlobal('Craft', {}));
  afterEach(() => vi.unstubAllGlobals());

  it('shows a new page size before the rows arrive, and requests it once', () => {
    const indexVisitor = visitor({source: 'section:news'});
    const {paginationState, paginationConfig} = useElementIndexPagination(
      {pagination},
      ref<ViewState>({showHeaderColumn: true, static: false}),
      indexVisitor,
      () => 'section:news'
    );

    paginationConfig.onPaginationChange({pageIndex: 0, pageSize: 100});
    // The page size select reports the same choice again on `change`.
    paginationConfig.onPaginationChange({pageIndex: 0, pageSize: 100});

    expect(paginationState.value.pageSize).toBe(100);
    expect(indexVisitor.visit).toHaveBeenCalledOnce();
    expect(indexVisitor.visit.mock.calls[0]?.[0]).toMatchObject({
      per_page: 100,
    });
  });

  it('restores the page size chosen for a source on its next load', () => {
    const viewState = ref<ViewState>({showHeaderColumn: true, static: false});
    const first = useElementIndexPagination(
      {pagination},
      viewState,
      visitor({source: 'section:news'}),
      () => 'section:news'
    );

    first.paginationConfig.onPaginationChange({pageIndex: 0, pageSize: 100});

    // Coming back to the index, whose first payload has the default size.
    const news = useElementIndexPagination(
      {pagination},
      viewState,
      visitor(),
      () => 'section:news'
    );
    const other = useElementIndexPagination(
      {pagination},
      viewState,
      visitor(),
      () => 'section:blog'
    );
    const fromUrl = useElementIndexPagination(
      {pagination: {...pagination, per_page: 250}},
      viewState,
      visitor({per_page: '250'}),
      () => 'section:news'
    );

    expect(news.restore()).toEqual({
      params: {per_page: 100},
      only: ['data', 'pagination'],
    });
    expect(other.restore()).toBeNull();
    expect(fromUrl.restore()).toBeNull();
  });
});
