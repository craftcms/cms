import {nextTick, reactive, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {SortItem} from '@/common/types';
import type {ViewState} from '@/modules/elements/types/view-state';
import {useElementIndexSort} from './useElementIndexSort';

const route = {url: () => '/entries'};

function viewState(sort?: Array<SortItem>) {
  return ref<ViewState>({
    inlineEditing: false,
    mode: 'table',
    showHeaderColumn: false,
    static: false,
    sources: sort ? {'*': {sort}} : {},
  });
}

function querySort(visit: ReturnType<typeof vi.fn>) {
  return visit.mock.calls.at(-1)?.[0].sort;
}

describe('useElementIndexSort', () => {
  beforeEach(() => vi.stubGlobal('Craft', {pageTrigger: 'page'}));
  afterEach(() => vi.unstubAllGlobals());

  it('sends previous fields as tie-breakers while exposing one active sort', () => {
    const visit = vi.fn();
    const props = reactive({
      sort: [
        {field: 'title', direction: 'asc' as const},
        {field: 'dateCreated', direction: 'desc' as const},
      ],
      sortOptions: [
        {label: 'Author', value: 'author', defaultDir: 'asc' as const},
      ],
    });
    const sort = useElementIndexSort(props, viewState(), {
      route,
      visitor: {currentQuery: () => ({}), visit, merge: vi.fn()},
    });

    sort.sortingConfig.onSortingChange([{id: 'author', desc: false}]);

    expect(sort.sortingState.value).toEqual([{id: 'title', desc: false}]);
    expect(querySort(visit)).toEqual({
      0: {field: 'author', direction: 'asc'},
      1: {field: 'title', direction: 'asc'},
      2: {field: 'dateCreated', direction: 'desc'},
    });
  });

  it('retains rapid sort choices before the server responds', () => {
    const visit = vi.fn();
    const sort = useElementIndexSort(
      {
        sort: [
          {field: 'title', direction: 'asc'},
          {field: 'dateCreated', direction: 'desc'},
        ],
        sortOptions: [
          {label: 'Author', value: 'author', defaultDir: 'asc'},
          {label: 'Post Date', value: 'postDate', defaultDir: 'desc'},
        ],
      },
      viewState(),
      {route, visitor: {currentQuery: () => ({}), visit, merge: vi.fn()}}
    );

    sort.sortField.value = 'author';
    sort.sortField.value = 'postDate';

    expect(querySort(visit)).toEqual({
      0: {field: 'postDate', direction: 'desc'},
      1: {field: 'author', direction: 'asc'},
      2: {field: 'title', direction: 'asc'},
      3: {field: 'dateCreated', direction: 'desc'},
    });
  });

  it('always requests score descending without tie-breakers', () => {
    const visit = vi.fn();
    const sort = useElementIndexSort(
      {
        sort: [{field: 'title', direction: 'asc'}],
        sortOptions: [{label: 'Title', value: 'title', defaultDir: 'asc'}],
      },
      viewState(),
      {route, visitor: {currentQuery: () => ({}), visit, merge: vi.fn()}}
    );

    sort.sortField.value = 'score';

    expect(querySort(visit)).toEqual({
      0: {field: 'score', direction: 'desc'},
    });
  });

  it('always requests custom ordering ascending', () => {
    const visit = vi.fn();
    const sort = useElementIndexSort(
      {
        sort: [{field: 'title', direction: 'asc'}],
        sortOptions: [{label: 'Custom', value: 'sortOrder', defaultDir: 'asc'}],
      },
      viewState(),
      {route, visitor: {currentQuery: () => ({}), visit, merge: vi.fn()}}
    );

    sort.onSortingChange([{id: 'sortOrder', desc: true}]);

    expect(querySort(visit)).toEqual({
      0: {field: 'sortOrder', direction: 'asc'},
      1: {field: 'title', direction: 'asc'},
    });
  });

  it('does not persist score and restores non-score history afterward', async () => {
    const visit = vi.fn();
    const persisted = [
      {field: 'title', direction: 'asc' as const},
      {field: 'dateCreated', direction: 'desc' as const},
    ];
    const state = viewState(persisted);
    const props = reactive({
      sort: persisted,
      sortOptions: [
        {label: 'Date', value: 'dateCreated', defaultDir: 'desc' as const},
      ],
    });
    const sort = useElementIndexSort(props, state, {
      route,
      visitor: {currentQuery: () => ({}), visit, merge: vi.fn()},
    });

    props.sort = [{field: 'score', direction: 'desc'}];
    await nextTick();

    expect(state.value.sources?.['*']?.sort).toEqual(persisted);

    sort.sortField.value = 'dateCreated';

    expect(querySort(visit)).toEqual({
      0: {field: 'dateCreated', direction: 'desc'},
      1: {field: 'title', direction: 'asc'},
    });
  });
});
