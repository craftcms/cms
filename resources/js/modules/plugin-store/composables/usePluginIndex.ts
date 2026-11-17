import {
  computed,
  onScopeDispose,
  shallowRef,
  toValue,
  watch,
  type MaybeRefOrGetter,
} from 'vue';
import {isCancel} from '@craftcms/ui/utilities/api/http';
import {getPlugins, type PluginIndexFilter} from '../api';
import type {PluginSummary, SortDirection} from '../types';

export interface PluginIndexSort {
  orderBy: string;
  direction: SortDirection;
}

/**
 * A plugin listing that loads page by page. Changing the filter or the sort
 * starts over from the first page.
 *
 * Pass a `null` sort to wait for one; pass `false` for a listing the API
 * orders itself.
 */
export function usePluginIndex(
  filter: MaybeRefOrGetter<PluginIndexFilter>,
  sort: MaybeRefOrGetter<PluginIndexSort | null | false>
) {
  const plugins = shallowRef<PluginSummary[]>([]);
  const nextPage = shallowRef(1);
  const hasMore = shallowRef(true);
  const loading = shallowRef(false);
  const failed = shallowRef(false);
  let controller: AbortController | null = null;

  async function load(): Promise<void> {
    const currentSort = toValue(sort);

    if (loading.value || !hasMore.value || currentSort === null) {
      return;
    }

    controller = new AbortController();
    const {signal} = controller;
    loading.value = true;

    try {
      const response = await getPlugins(
        toValue(filter),
        {page: nextPage.value, ...currentSort},
        signal
      );

      plugins.value = [...plugins.value, ...response.plugins];
      hasMore.value = response.currentPage < response.total;
      nextPage.value = response.currentPage + 1;
    } catch (error) {
      if (!isCancel(error)) {
        failed.value = true;
        hasMore.value = false;
      }
    } finally {
      if (!signal.aborted) {
        loading.value = false;
      }
    }
  }

  function reset(): void {
    controller?.abort();
    plugins.value = [];
    nextPage.value = 1;
    hasMore.value = true;
    loading.value = false;
    failed.value = false;
  }

  watch(
    () => [JSON.stringify(toValue(filter)), JSON.stringify(toValue(sort))],
    () => {
      reset();
      void load();
    },
    {immediate: true}
  );

  onScopeDispose(() => controller?.abort());

  return {
    plugins: computed(() => plugins.value),
    loading: computed(() => loading.value),
    hasMore: computed(() => hasMore.value),
    failed: computed(() => failed.value),
    loadMore: load,
  };
}
