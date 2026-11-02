import {effectScope, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ContentIndexData} from './useContentIndexData';
import {indexData} from '../fixtures/indexData';
import {useDetachedElementIndex} from './useDetachedElementIndex';

let scope: ReturnType<typeof effectScope>;

afterEach(() => {
  scope?.stop();
  vi.useRealTimers();
  localStorage.clear();
  vi.unstubAllGlobals();
});

it.each(['earlier first', 'latest first'])(
  'keeps the latest detached response and loading state when responses arrive %s',
  async (order) => {
    vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
    const first = deferred<ContentIndexData>();
    const second = deferred<ContentIndexData>();
    const fetch = vi
      .fn()
      .mockReturnValueOnce(first.promise)
      .mockReturnValueOnce(second.promise);
    scope = effectScope();
    const index = scope.run(() =>
      useDetachedElementIndex({initial: indexData(), fetch})
    )!;
    const firstLoad = index.refresh({selectId: 1});
    const secondLoad = index.refresh({selectId: 2});

    if (order === 'earlier first') {
      first.resolve(indexData(1));
      await firstLoad;
      expect(index.view.elementIndex.data).toEqual([]);
      expect(index.view.loading.value).toBe(true);
      second.resolve(indexData(2));
      await secondLoad;
    } else {
      second.resolve(indexData(2));
      await secondLoad;
      first.resolve(indexData(1));
      await firstLoad;
    }

    expect(index.view.elementIndex.data.map(({id}) => id)).toEqual([2]);
    expect(index.view.loading.value).toBe(false);
    expect(index.view.selection.selectedIds.value).toEqual([2]);
  }
);

it('requests the rows a payload left out, for the view mode it shows', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  const fetch = vi.fn().mockResolvedValue(indexData(1));
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({
      initial: indexData(undefined, {data: undefined, pagination: undefined}),
      fetch,
    })
  )!;

  expect(index.view.pending.value).toBe(true);

  index.restore();

  expect(fetch).toHaveBeenCalledWith(
    expect.objectContaining({viewMode: 'table'})
  );
  await vi.waitFor(() => expect(index.view.pending.value).toBe(false));
  expect(index.view.elementIndex.data.map(({id}) => id)).toEqual([1]);
});

it('keeps the loading state off for a thumb size change', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  const response = deferred<ContentIndexData>();
  const fetch = vi.fn().mockReturnValueOnce(response.promise);
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({initial: indexData(), fetch})
  )!;

  index.view.thumbSize.value = 'md';

  expect(fetch).toHaveBeenCalledWith(
    expect.objectContaining({thumbSize: 'md'})
  );
  expect(index.view.loading.value).toBe(false);
  response.resolve(indexData(1));
  await response.promise;
  await nextTick();
  expect(index.view.loading.value).toBe(false);
});

// The new source's default mode comes from the server, so its thumbnail size
// can only be asked for once its rows arrive.
it('gives a source switch the new source’s own mode and thumbnail size', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  localStorage.setItem(
    'Craft-test.elementindex.Entry.modal',
    JSON.stringify({
      sources: {
        '*': {mode: 'thumbs'},
        'section:news': {thumbSizes: {table: 'md'}},
      },
    })
  );
  const source = {type: 'native' as const, key: 'section:news', label: 'News'};
  const fetch = vi
    .fn()
    .mockResolvedValueOnce(indexData(1, {source}))
    .mockResolvedValueOnce(indexData(2, {source}));
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({
      initial: indexData(undefined, {
        viewState: {mode: 'thumbs'},
        viewModes: [
          {mode: 'table', title: 'Table', icon: 'table'},
          {mode: 'thumbs', title: 'Thumbnails', icon: 'grid'},
        ],
      }),
      fetch,
    })
  )!;

  index.changeSource(source.key);
  await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(2));

  expect(fetch.mock.calls[0]![0]).toEqual({source: 'section:news'});
  expect(fetch.mock.calls[1]![0]).toEqual({
    source: 'section:news',
    thumbSize: 'md',
  });
  expect(index.view.mode.value).toBe('table');
});

it('uses the new source default when clearing a search without a saved sort', async () => {
  vi.useFakeTimers();
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  const source = {type: 'native' as const, key: 'section:news', label: 'News'};
  const score = [{field: 'score', direction: 'desc' as const}];
  const fetch = vi
    .fn()
    .mockResolvedValueOnce(indexData(1, {search: 'needle', sort: score}))
    .mockResolvedValueOnce(
      indexData(2, {source, search: 'needle', sort: score})
    )
    .mockResolvedValueOnce(
      indexData(3, {source, sort: [{field: 'dateCreated', direction: 'desc'}]})
    );
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({initial: indexData(), fetch})
  )!;
  index.view.search.value = 'needle';
  await nextTick();
  await vi.advanceTimersByTimeAsync(500);
  index.changeSource(source.key);
  await nextTick();
  await Promise.resolve();
  index.view.search.value = '';
  await nextTick();
  await vi.advanceTimersByTimeAsync(500);

  expect(fetch).toHaveBeenLastCalledWith({
    source: 'section:news',
    sort: [],
  });
  expect(index.view.elementIndex.sort).toEqual([
    {field: 'dateCreated', direction: 'desc'},
  ]);
});

it('returns the sort, columns and thumb size to their defaults in one request', () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  localStorage.setItem(
    'Craft-test.elementindex.Entry.modal',
    JSON.stringify({
      sources: {'*': {visible: ['author'], thumbSizes: {table: 'md'}}},
    })
  );
  const fetch = vi.fn().mockResolvedValue(indexData(1));
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({
      initial: indexData(undefined, {
        sort: [{field: 'title', direction: 'asc'}],
        defaultSort: [{field: 'postDate', direction: 'desc'}],
        tableColumns: [
          {label: 'Author', value: 'author'},
          {label: 'Post Date', value: 'postDate'},
        ],
        defaultTableColumns: ['postDate'],
      }),
      fetch,
    })
  )!;

  expect(index.view.viewCustomized.value).toBe(true);

  index.view.resetView();

  expect(index.view.viewCustomized.value).toBe(false);
  expect(index.view.sortField.value).toBe('postDate');
  expect(index.view.sortDirection.value).toBe('desc');
  expect(index.view.tableColumns.value).toEqual(['postDate']);
  expect(index.view.thumbSize.value).toBe('sm');
  expect(fetch).toHaveBeenCalledTimes(1);
  expect(fetch.mock.calls[0]![0]).not.toHaveProperty('sort');
  expect(fetch.mock.calls[0]![0]).not.toHaveProperty('columns');
  expect(fetch.mock.calls[0]![0]).not.toHaveProperty('thumbSize');
});

it('keeps rows selected on other pages until the listed elements change', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  const page = (current: number, ids: number[], search = '') =>
    indexData(undefined, {
      search,
      data: ids.map((id) => ({id, label: `Entry ${id}`})),
      pagination: {
        total: 4,
        per_page: 2,
        current_page: current,
        last_page: 2,
        next_page_url: null,
        prev_page_url: null,
        from: current * 2 - 1,
        to: current * 2,
      },
    } as Partial<ContentIndexData>);
  const fetch = vi
    .fn()
    .mockResolvedValueOnce(page(2, [3, 4]))
    .mockResolvedValueOnce(page(2, [4]))
    .mockResolvedValueOnce(page(1, [1, 2], 'Entry'));
  scope = effectScope();
  const index = scope.run(() =>
    useDetachedElementIndex({initial: page(1, [1, 2]), fetch})
  )!;
  const {table, selection} = index.view;
  const row = (id: number) =>
    table.getRowModel().rows.find(({original}) => original.id === id)!;

  selection.selectRow(row(1), {checked: true});
  table.setPageIndex(1);
  await vi.waitFor(() => expect(row(3)).toBeDefined());
  selection.selectRow(row(3), {checked: true});

  expect(selection.selectedIds.value).toEqual([1, 3]);
  expect(selection.selectedRows.value.map(({label}) => label)).toEqual([
    'Entry 1',
    'Entry 3',
  ]);

  await index.refresh();

  expect(selection.selectedIds.value).toEqual([1]);

  index.view.search.value = 'Entry';
  await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(3));
  await vi.waitFor(() => expect(index.view.elementIndex.search).toBe('Entry'));

  expect(selection.selectedIds.value).toEqual([]);
});

function deferred<Value>() {
  let resolve!: (value: Value) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<Value>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return {promise, resolve, reject};
}
