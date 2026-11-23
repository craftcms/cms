import {actionClient} from '@craftcms/ui';
import {nextTick} from 'vue';
import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
import {
  useDetachedElementIndex,
  type UseDetachedElementIndexOptions,
} from '@/modules/elements/index/composables/useDetachedElementIndex';
import {normalizeSort} from '@/modules/elements/index/composables/useElementIndexSort';
import type {IndexQueryParams} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
import {
  appendPayloadAssets,
  type NestedContentIndexData,
} from '../nested-elements/nested-elements';

export interface UseEmbeddedIndexElementsOptions<
  Source extends NestedContentIndexData,
> extends Omit<
  UseDetachedElementIndexOptions<Source>,
  'fetch' | 'data' | 'pinnedColumn' | 'inlineEditing' | 'onLoaded'
> {
  /** Params that scope every request, or `null` to keep the first payload. */
  requestParams: () => Record<string, unknown> | null;
  showHeaderColumn?: boolean;
  withoutScore?: boolean;
  /** Whether inline inputs can be requested. Defaults to true. */
  editable?: () => boolean;
  saveInline?: (
    body: URLSearchParams
  ) => Promise<InlineEditingSaveResult | false>;
  /** Gets the query with the defaults filled in, once the assets are added. */
  onLoaded?: (payload: Source, query: IndexQueryParams) => void | Promise<void>;
}

/**
 * Lists an embedded index's elements from its first payload, and loads later
 * pages, sorts and searches from the `embeddedIndex` context.
 */
export function useEmbeddedIndexElements<Source extends NestedContentIndexData>(
  options: UseEmbeddedIndexElementsOptions<Source>
) {
  const {
    requestParams,
    showHeaderColumn,
    withoutScore,
    editable = () => true,
    saveInline,
    onLoaded,
    ...detachedOptions
  } = options;
  const {initial} = options;
  const renderedMode = initial.viewState.mode;
  const renderedColumns = initial.defaultTableColumns.filter(
    (column) => column !== 'title'
  );
  const initialSort = normalizeSort(initial.sort, {withoutScore});
  let loadingInlineInputs = false;

  /** Fills in the view mode, columns and sort the first payload was rendered with. */
  function completeQuery(query: IndexQueryParams): IndexQueryParams {
    return {
      viewMode: renderedMode,
      columns: renderedColumns,
      ...query,
      sort: normalizeSort(query.sort ?? initialSort, {withoutScore}),
    };
  }

  /** Reloads the rows with their inline inputs, to start editing inline. */
  async function loadInlineInputs(): Promise<void> {
    loadingInlineInputs = true;

    try {
      await model.load();
    } finally {
      loadingInlineInputs = false;
    }
  }

  const model = useDetachedElementIndex<Source>({
    ...detachedOptions,
    inlineEditing: saveInline
      ? {load: loadInlineInputs, save: saveInline}
      : undefined,
    pinnedColumn:
      showHeaderColumn === false
        ? null
        : {key: 'title', label: initial.elementDisplayName},
    data: (rows, mode, elementIndex) =>
      elementIndex.viewState.mode === mode ? rows : [],
    fetch: async (query) => {
      const params = requestParams();

      if (!params) {
        return initial;
      }

      const requestQuery = completeQuery(query);
      const {data} = await actionClient.post<Source>(getElements.url(), {
        context: 'embeddedIndex',
        source: '__IMP__',
        ...params,
        ...requestQuery,
        ...(loadingInlineInputs && editable() ? {editable: true} : {}),
        showInGrid: requestQuery.viewMode === 'cards',
      });

      return data;
    },
    onLoaded: async (payload, query) => {
      await nextTick();
      await appendPayloadAssets(payload);
      await onLoaded?.(payload, completeQuery(query));
    },
  });

  return {model, initialSort, loadInlineInputs};
}
