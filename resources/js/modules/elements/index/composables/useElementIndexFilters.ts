import {useTimeoutFn} from '@vueuse/core';
import {shallowRef, toValue, watch, type MaybeRefOrGetter, type Ref} from 'vue';
import type {SortItem} from '@/common/types';
import {sameRules, withoutRules} from '@/modules/conditions/conditionTree';
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
  /**
   * The condition the filters start from — a custom source's own — which
   * filters changed from it replace.
   */
  baseline?: MaybeRefOrGetter<ConditionConfig | null>;
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
  /** Sets the filters to what a visit already requested, without another one. */
  sync: (values: {
    search?: string | null;
    status?: string | null;
    conditions?: ConditionConfig | null;
  }) => void;
}

/** Applies shared filters immediately or after typing, deferring while busy. */
export function useElementIndexFilters(
  visitor: IndexVisitor,
  options: UseElementIndexFiltersOptions = {}
): ElementIndexFilters {
  const search = shallowRef(options.search ?? '');
  const status = shallowRef(options.status ?? '');
  const baseline = () => toValue(options.baseline) ?? null;
  const conditions = shallowRef<ConditionConfig | null>(
    options.conditions ?? baseline()
  );
  let pending = false;

  /** The filters as the query has them: none while they're the baseline's. */
  function conditionParam(): ConditionConfig | null {
    const base = baseline();

    if (!base) return conditions.value;
    if (sameRules(conditions.value, base)) return null;

    // Every rule removed still differs from the baseline's.
    return conditions.value ?? withoutRules(base);
  }

  function parameters(): IndexQueryParams {
    return {
      ...toValue(options.params),
      search: search.value || null,
      status: status.value || null,
      condition: conditionParam() as IndexQueryValue,
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

  watch(search, (value) => {
    // A synced search was already requested.
    if ((value || null) === (applied?.search ?? null)) {
      applySearch.stop();
      return;
    }

    applySearch.start();
  });
  watch([status, conditions], () => apply());
  // A new source brings its own baseline, which unchanged filters follow.
  watch(baseline, (next, previous) => {
    if (sameRules(conditions.value, previous)) {
      conditions.value = next;
      applied = parameters();
    }
  });
  watch(
    () => toValue(options.busy) ?? false,
    (busy) => {
      if (!busy && pending) {
        apply();
      }
    }
  );

  function sync(values: {
    search?: string | null;
    status?: string | null;
    conditions?: ConditionConfig | null;
  }): void {
    applySearch.stop();
    search.value = values.search ?? '';
    status.value = values.status ?? '';
    conditions.value = values.conditions ?? baseline();
    applied = parameters();
    lastRequestedSearch = Boolean(applied.search);
  }

  return {
    search,
    status,
    conditions,
    submit,
    cancelPendingSearch: applySearch.stop,
    sync,
  };
}
