import {computed, onScopeDispose, shallowRef} from 'vue';
import type {PaginationData} from '@/common/types';

export interface PaginatedRows<TData> {
  rows: TData[];
  pagination: PaginationData;
}

export function usePaginatedRows<TData, TRequest>(
  getLoader: () =>
    | ((
        request: TRequest,
        signal: AbortSignal
      ) => Promise<PaginatedRows<TData>>)
    | undefined,
  onError: (error: unknown) => void
) {
  const rows = shallowRef<TData[]>([]);
  const pagination = shallowRef<PaginationData | null>(null);
  const loading = shallowRef(false);
  const request = shallowRef<AbortController>();

  onScopeDispose(() => request.value?.abort());

  async function load(params: TRequest): Promise<boolean> {
    const loader = getLoader();
    if (!loader) return true;

    request.value?.abort();
    const controller = new AbortController();
    request.value = controller;
    loading.value = true;

    try {
      const result = await loader(params, controller.signal);
      if (controller.signal.aborted) return false;

      rows.value = result.rows;
      pagination.value = result.pagination;
      return true;
    } catch (error) {
      if (!controller.signal.aborted) onError(error);
      return false;
    } finally {
      if (request.value === controller) loading.value = false;
    }
  }

  return {
    rows,
    pagination,
    loading,
    request: computed(() => request.value?.signal),
    load,
  };
}
