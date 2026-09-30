import {useTimeoutFn} from '@vueuse/core';
import {shallowRef, toValue, watch, type MaybeRefOrGetter, type Ref} from 'vue';
import type {SortItem} from '@/common/types';
import type {ConditionConfig} from '@/modules/conditions/types';
import type {
  IndexQueryParams,
  IndexQueryValue,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';

const SEARCH_DEBOUNCE_MS = 500;

export interface UseElementIndexFiltersOptions {
  search?: string | null;
  status?: string | null;
  conditions?: ConditionConfig | null;
  busy?: MaybeRefOrGetter<boolean>;
  preferredSort?: MaybeRefOrGetter<Array<SortItem>>;
  params?: MaybeRefOrGetter<IndexQueryParams>;
}

export interface ElementIndexFilters {
  search: Ref<string>;
  status: Ref<string>;
  conditions: Ref<ConditionConfig | null>;
  submit: () => void;
  cancelPendingSearch: () => void;
}

/** Applies shared filters immediately or after typing, deferring while busy. */
export function useElementIndexFilters(
  visitor: IndexVisitor,
  options: UseElementIndexFiltersOptions = {}
): ElementIndexFilters {
  const search = shallowRef(options.search ?? '');
  const status = shallowRef(options.status ?? '');
  const conditions = shallowRef<ConditionConfig | null>(
    options.conditions ?? null
  );
  let pending = false;

  function parameters(): IndexQueryParams {
    return {
      ...toValue(options.params),
      search: search.value || null,
      status: status.value || null,
      condition: (conditions.value ?? null) as IndexQueryValue,
    };
  }

  function filterState(params: IndexQueryParams): string {
    return JSON.stringify(
      Object.fromEntries(
        Object.entries(params).filter(([key]) => key !== 'viewMode')
      )
    );
  }

  // Null means a request was unsuccessful; the next apply must retry.
  let applied: IndexQueryParams | null = parameters();
  // Retain the search transition even before its response, to restore the sort.
  let lastRequestedSearch = Boolean(applied.search);
  let latestRequest = 0;

  function apply(replace = false): void {
    if (toValue(options.busy) ?? false) {
      pending = true;

      return;
    }

    pending = false;
    const next = parameters();

    if (applied !== null && filterState(applied) === filterState(next)) {
      return;
    }

    const searching = Boolean(next.search);
    const wasSearching = lastRequestedSearch;
    const currentSearching = Boolean(visitor.currentQuery().search);
    let sort:
      | Array<{field: string; direction: SortItem['direction']}>
      | undefined;

    if (searching && !currentSearching) {
      sort = [{field: 'score', direction: 'desc'}];
    } else if (!searching && (wasSearching || currentSearching)) {
      sort = toValue(options.preferredSort) ?? [];
    }

    applied = next;
    lastRequestedSearch = searching;
    const requestId = ++latestRequest;

    visitor.merge(
      {...next, ...(sort !== undefined ? {sort} : {})},
      {
        resetPage: true,
        replace,
        onFinish: ({completed}) => {
          if (!completed && requestId === latestRequest) {
            applied = null;
          }
        },
      }
    );
  }

  const applySearch = useTimeoutFn(() => apply(true), SEARCH_DEBOUNCE_MS, {
    immediate: false,
  });

  function submit(): void {
    applySearch.stop();
    apply();
  }

  watch(search, () => applySearch.start());
  watch([status, conditions], () => apply());
  watch(
    () => toValue(options.busy) ?? false,
    (busy) => {
      if (!busy && pending) {
        apply();
      }
    }
  );

  return {
    search,
    status,
    conditions,
    submit,
    cancelPendingSearch: applySearch.stop,
  };
}
