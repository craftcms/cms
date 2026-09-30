import {effectScope, nextTick, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  createDetachedIndexVisitor,
  type IndexQueryParams,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useElementIndexFilters} from './useElementIndexFilters';

function detachedVisitor(initial: IndexQueryParams = {}) {
  const load = vi.fn(async (_query: IndexQueryParams) => true);

  return {
    visitor: createDetachedIndexVisitor((query) => load(query), initial),
    load,
  };
}

describe('useElementIndexFilters', () => {
  let scope: ReturnType<typeof effectScope>;

  afterEach(() => {
    scope?.stop();
    vi.useRealTimers();
    window.history.replaceState({}, '', '/');
  });

  it('debounces search and applies it immediately on submit', async () => {
    vi.useFakeTimers();
    const indexVisitor = detachedVisitor({page: 3});
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor)
    )!;

    filters.search.value = 'n';
    await nextTick();
    filters.search.value = 'needle';
    await nextTick();
    await vi.advanceTimersByTimeAsync(499);

    expect(indexVisitor.load).not.toHaveBeenCalled();

    filters.submit();

    expect(indexVisitor.load).toHaveBeenCalledOnce();
    expect(indexVisitor.load).toHaveBeenLastCalledWith({
      search: 'needle',
      sort: [{field: 'score', direction: 'desc'}],
    });

    await vi.advanceTimersByTimeAsync(500);
    expect(indexVisitor.load).toHaveBeenCalledOnce();
  });

  it('applies only the latest search after the debounce', async () => {
    vi.useFakeTimers();
    const indexVisitor = detachedVisitor();
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor)
    )!;

    filters.search.value = 'n';
    await nextTick();
    await vi.advanceTimersByTimeAsync(200);
    filters.search.value = 'needle';
    await nextTick();
    await vi.advanceTimersByTimeAsync(499);

    expect(indexVisitor.load).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(1);

    expect(indexVisitor.load).toHaveBeenCalledOnce();
    expect(indexVisitor.load).toHaveBeenCalledWith({
      search: 'needle',
      sort: [{field: 'score', direction: 'desc'}],
    });
    filters.submit();
    expect(indexVisitor.load).toHaveBeenCalledOnce();
  });

  it('defers changed filters while busy and applies their latest values once', async () => {
    const busy = shallowRef(true);
    const indexVisitor = detachedVisitor();
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor, {busy})
    )!;

    filters.status.value = 'draft';
    await nextTick();
    filters.status.value = 'pending';
    filters.conditions.value = {
      class: 'EntryCondition',
      conditionRules: [],
    };
    await nextTick();

    expect(indexVisitor.load).not.toHaveBeenCalled();

    busy.value = false;
    await nextTick();

    expect(indexVisitor.load).toHaveBeenCalledOnce();
    expect(indexVisitor.load).toHaveBeenCalledWith({
      status: 'pending',
      condition: {class: 'EntryCondition', conditionRules: []},
    });
  });

  it('does not visit when an explicit submit changes nothing, including the view mode', () => {
    const indexVisitor = detachedVisitor({search: 'needle', status: 'pending'});
    scope = effectScope();
    const params = shallowRef({viewMode: 'table'});
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor, {
        search: 'needle',
        status: 'pending',
        params,
      })
    )!;

    params.value = {viewMode: 'cards'};
    filters.submit();

    expect(indexVisitor.load).not.toHaveBeenCalled();
  });

  it('applies status and condition immediately and resets the page', async () => {
    const indexVisitor = detachedVisitor({page: 3, source: 'section:news'});
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor)
    )!;

    filters.status.value = 'pending';
    await nextTick();

    expect(indexVisitor.load).toHaveBeenCalledWith({
      source: 'section:news',
      status: 'pending',
    });

    filters.conditions.value = {class: 'EntryCondition', conditionRules: []};
    await nextTick();

    expect(indexVisitor.load).toHaveBeenCalledTimes(2);
    expect(indexVisitor.load).toHaveBeenLastCalledWith({
      source: 'section:news',
      status: 'pending',
      condition: {class: 'EntryCondition', conditionRules: []},
    });
  });

  it('uses score while searching and restores the preferred sort when cleared', async () => {
    vi.useFakeTimers();
    const indexVisitor = detachedVisitor();
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor, {
        preferredSort: [{field: 'dateUpdated', direction: 'desc'}],
      })
    )!;

    filters.search.value = 'needle';
    await nextTick();
    await vi.advanceTimersByTimeAsync(500);
    filters.search.value = '';
    await nextTick();
    await vi.advanceTimersByTimeAsync(500);

    expect(indexVisitor.load).toHaveBeenNthCalledWith(1, {
      search: 'needle',
      sort: [{field: 'score', direction: 'desc'}],
    });
    expect(indexVisitor.load).toHaveBeenNthCalledWith(2, {
      sort: [{field: 'dateUpdated', direction: 'desc'}],
    });
  });

  it('reapplies cleared filters after a superseded request', async () => {
    const indexVisitor = detachedVisitor();
    indexVisitor.load.mockResolvedValueOnce(false);
    scope = effectScope();
    const filters = scope.run(() =>
      useElementIndexFilters(indexVisitor.visitor)
    )!;
    filters.search.value = 'needle';
    filters.submit();
    await nextTick();
    filters.search.value = '';
    filters.submit();

    expect(indexVisitor.load).toHaveBeenCalledTimes(2);
    expect(indexVisitor.load).toHaveBeenLastCalledWith({sort: []});
  });
});
