import {shallowRef} from 'vue';
import {
  useContentIndexData,
  type ContentIndexData,
  type ElementIndexRow,
} from '@/modules/elements/index/composables/useContentIndexData';
import {useElementIndex} from '@/modules/elements/index/composables/useElementIndex';
import {
  createDetachedIndexVisitor,
  type IndexQueryParams,
  type IndexQueryValue,
} from '@/modules/elements/index/composables/useElementIndexVisits';

interface UseDetachedElementIndexOptions {
  initial: ContentIndexData;
  fetch: (query: IndexQueryParams) => Promise<ContentIndexData>;
  enableRowSelection?: (row: ElementIndexRow) => boolean;
}

/** Adds local payloads and request ordering to the shared index. */
export function useDetachedElementIndex(
  options: UseDetachedElementIndexOptions
) {
  const payload = shallowRef(options.initial);
  const loading = shallowRef(false);

  /** Returns whether this response became the current payload. */
  async function load(
    query: IndexQueryParams,
    isCurrent: () => boolean
  ): Promise<boolean> {
    loading.value = true;

    try {
      const response = await options.fetch(query);

      if (isCurrent()) {
        payload.value = response;
        return true;
      }

      return false;
    } finally {
      if (isCurrent()) {
        loading.value = false;
      }
    }
  }

  const visitor = createDetachedIndexVisitor(load, {
    search: options.initial.search || undefined,
    status: options.initial.status || undefined,
    condition: (options.initial.currentCondition ?? undefined) as
      | IndexQueryValue
      | undefined,
  });
  const elementIndex = useContentIndexData(undefined, payload);
  const index = useElementIndex({
    elementIndex,
    visitor,
    loading,
    busy: loading,
    refresh: async () => {
      let applied = false;
      await visitor.visit(visitor.currentQuery(), {
        onFinish: (result) => {
          applied = result.completed;
        },
      });
      return applied;
    },
    enableRowSelection: options.enableRowSelection,
  });

  return index.model;
}
