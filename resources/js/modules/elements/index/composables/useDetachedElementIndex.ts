import type {ColumnDef} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/modules/admin-table/craftTable';
import {shallowRef, type ComputedRef, type MaybeRefOrGetter} from 'vue';
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
import type {ViewMode} from '@/modules/elements/types/view-state';
import type {ElementIndexContext} from '@/modules/elements/index/index-context';
import type {InlineEditingSaveResult} from './useInlineEditing';
import type {ExportElementIndex} from '../types/model';

export interface UseDetachedElementIndexOptions<
  Source extends ContentIndexData = ContentIndexData,
> {
  initial: Source;
  initialQuery?: IndexQueryParams;
  fetch: (query: IndexQueryParams) => Promise<Source>;
  onLoaded?: (payload: Source, query: IndexQueryParams) => void | Promise<void>;
  onError?: (cause: unknown) => void;
  storageKey?: string;
  busy?: MaybeRefOrGetter<boolean>;
  readOnly?: MaybeRefOrGetter<boolean>;
  filterContext?: (
    payload: Source
  ) => Pick<ElementIndexContext, 'fieldLayouts' | 'extraParams'>;
  inlineEditing?: {
    load(): Promise<void>;
    save(body: URLSearchParams): Promise<InlineEditingSaveResult | false>;
  };
  exportElements?: ExportElementIndex;
  rowReorder?: {
    enabled: MaybeRefOrGetter<boolean>;
    move(from: number, to: number): void | Promise<void>;
  };
  pinnedColumn?: {key: string; label: string} | null;
  enableRowSelection?: (row: ElementIndexRow) => boolean;
  data?: (
    rows: ElementIndexRow[],
    mode: ViewMode['mode'],
    elementIndex: ReturnType<typeof useContentIndexData>
  ) => ElementIndexRow[];
  columns?: (
    columns: ComputedRef<Array<ColumnDef<CraftTableFeatures, ElementIndexRow>>>,
    context: {elementIndex: ReturnType<typeof useContentIndexData>}
  ) => ComputedRef<Array<ColumnDef<CraftTableFeatures, ElementIndexRow>>>;
}

/** Adds local payloads and request ordering to the shared index. */
export function useDetachedElementIndex<
  Source extends ContentIndexData = ContentIndexData,
>(options: UseDetachedElementIndexOptions<Source>) {
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
        loading.value = false;
        await options.onLoaded?.(response, query);
        return true;
      }

      return false;
    } catch (cause) {
      if (isCurrent() && options.onError) {
        options.onError(cause);
        return false;
      }

      throw cause;
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
    ...options.initialQuery,
  });
  const elementIndex = useContentIndexData(undefined, payload);
  const index = useElementIndex({
    elementIndex,
    visitor,
    loading,
    busy: options.busy ?? loading,
    readOnly: options.readOnly,
    storageKey: options.storageKey,
    filterContext: options.filterContext
      ? () => options.filterContext!(payload.value)
      : undefined,
    inlineEditing: options.inlineEditing,
    exportElements: options.exportElements,
    rowReorder: options.rowReorder,
    pinnedColumn: options.pinnedColumn,
    data: options.data,
    columns: options.columns,
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

  return {
    ...index.model,
    payload,
    visitor,
    query: visitor.query,
    cancelPendingSearch: index.cancelPendingSearch,
    load: (query: IndexQueryParams = visitor.currentQuery()) =>
      visitor.visit(query),
    restore: index.restore,
  };
}
