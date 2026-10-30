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

function deferred<Value>() {
  let resolve!: (value: Value) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<Value>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return {promise, resolve, reject};
}
