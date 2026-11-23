import {t} from '@craftcms/ui';
import {
  computed,
  shallowRef,
  watch,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import {pageParam} from '@/common/craft/url';
import {exportElementIndex} from '@/modules/elements/index/export-element-index';
import {normalizeSort} from '@/modules/elements/index/composables/useElementIndexSort';
import type {UseDetachedElementIndexOptions} from '@/modules/elements/index/composables/useDetachedElementIndex';
import type {
  IndexQueryParams,
  IndexQueryValue,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';
import {
  appendPayloadAssets,
  markInvalidElements,
  nestedElementsErrorMessage,
  nestedIndexParams,
  type NestedContentIndexData,
  type NestedElementsProps,
  type NestedIndexElement,
} from './nested-elements';
import {useEmbeddedIndexElements} from '../element-index/useEmbeddedIndexElements';

type NestedColumns =
  UseDetachedElementIndexOptions<NestedContentIndexData>['columns'];

export function useNestedElementsQuery(options: {
  props: () => NestedElementsProps;
  busy: Ref<boolean>;
  error: Ref<string>;
  onLoaded: () => Promise<void>;
  editable?: () => boolean;
  columns?: NestedColumns;
  readOnly?: MaybeRefOrGetter<boolean>;
  rowReorder?: UseDetachedElementIndexOptions<NestedContentIndexData>['rowReorder'];
  saveInline?: (
    body: URLSearchParams
  ) => Promise<InlineEditingSaveResult | false>;
}) {
  const props = options.props();
  const initial = props.index?.initial;
  const manager = props.manager;

  if (!initial || !manager) {
    throw new Error(
      'Nested index controls require initial data and a manager.'
    );
  }

  const prevalidate = shallowRef(Boolean(manager.prevalidate));
  const renderedMode = initial.viewState.mode;
  const initialSort = normalizeSort(initial.sort, {withoutScore: true});
  let preferredSort = initialSort;
  let appliedQuery: IndexQueryParams = {
    search: initial.search || undefined,
    status: initial.status || undefined,
    condition: (initial.currentCondition ?? undefined) as
      | IndexQueryValue
      | undefined,
    viewMode: renderedMode,
    columns: initial.defaultTableColumns.filter((column) => column !== 'title'),
    sort: initialSort,
  };

  void appendPayloadAssets(initial).catch((cause) => {
    options.error.value = nestedElementsErrorMessage(
      cause,
      t('Could not initialize nested elements.')
    );
  });

  const embedded = useEmbeddedIndexElements<NestedContentIndexData>({
    initial,
    storageKey: `nestedentries.${
      props.index?.indexSettings?.storageKey ?? manager.fieldId
    }`,
    busy: options.busy,
    readOnly: options.readOnly,
    filterContext: (payload) => {
      const currentManager = options.props().manager ?? manager;

      return {
        fieldLayouts: payload.fieldLayouts,
        extraParams: nestedIndexParams(
          currentManager,
          currentManager.ownerId,
          options.props().index?.initial
        ),
      };
    },
    requestParams: () => {
      const currentManager = options.props().manager;

      return currentManager
        ? {
            ...nestedIndexParams(
              currentManager,
              currentManager.ownerId,
              options.props().index?.initial
            ),
            elementType: currentManager.elementType,
            ...(prevalidate.value ? {prevalidate: true} : {}),
          }
        : null;
    },
    showHeaderColumn: props.index?.indexSettings?.showHeaderColumn,
    withoutScore: true,
    editable: () => Boolean(options.editable?.()),
    saveInline: options.saveInline,
    exportElements: exportElements,
    rowReorder: options.rowReorder,
    columns: options.columns,
    onLoaded: async (_payload, query) => {
      appliedQuery = query;
      preferredSort = normalizeSort(query.sort, {withoutScore: true});
      options.error.value = '';
      await options.onLoaded();
    },
    onError: (cause) => {
      options.error.value = nestedElementsErrorMessage(
        cause,
        t('Could not load nested elements.')
      );
    },
  });
  const index = embedded.model;

  index.restore();

  const {elementIndex, table, selection} = index.view;
  const elements = computed(
    () => index.view.data.value as NestedIndexElement[]
  );
  const page = computed({
    get: () => table.atoms.pagination.get().pageIndex + 1,
    set: (value: number) => table.setPageIndex(value - 1),
  });
  const pagination = computed(() => index.payload.value.pagination);
  const unfilteredTotal = computed(
    () => index.payload.value.pagination.unfilteredTotal ?? null
  );
  const payloadActions = computed(() => index.payload.value.actions);
  const exporters = computed(() => index.payload.value.exporters);

  function load(inlineEditing = false): Promise<void> {
    return inlineEditing ? embedded.loadInlineInputs() : index.load();
  }

  async function exportElements(
    format: ElementIndexExportFormat = 'csv',
    type?: string,
    selectedIds: ReadonlyArray<string | number> = [],
    limit?: number
  ): Promise<void> {
    const currentManager = options.props().manager;
    if (!currentManager || !type) {
      return;
    }

    options.busy.value = true;
    options.error.value = '';

    try {
      await exportElementIndex(
        {
          ...nestedIndexParams(
            currentManager,
            currentManager.ownerId,
            options.props().index?.initial
          ),
          elementType: currentManager.elementType,
          context: 'embeddedIndex',
          source: '__IMP__',
          criteria: {
            search:
              typeof appliedQuery.search === 'string'
                ? appliedQuery.search
                : '',
            status: appliedQuery.status || null,
          },
          sort: normalizeSort(appliedQuery.sort, {withoutScore: true}),
          condition: appliedQuery.condition ?? null,
        },
        format,
        type,
        selectedIds,
        limit
      );
    } catch (cause) {
      options.error.value = nestedElementsErrorMessage(
        cause,
        t('Could not export nested elements.')
      );
    } finally {
      options.busy.value = false;
    }
  }

  async function resetSearch(): Promise<void> {
    index.view.search.value = '';
    index.cancelPendingSearch();
    index.query.value = index.visitor.currentQuery([
      'search',
      'sort',
      pageParam(),
    ]);
    index.query.value.sort = preferredSort;

    await index.load();
  }

  function setInvalidElements(ids: number[]): void {
    prevalidate.value = true;
    index.payload.value = {
      ...index.payload.value,
      data: markInvalidElements(index.payload.value.data, ids),
    };
  }

  watch(
    () => options.props().manager?.prevalidate,
    (validate) => {
      if (validate && !prevalidate.value) {
        prevalidate.value = true;
        void load();
      }
    }
  );

  return {
    model: index,
    elementIndex,
    payload: index.payload,
    elements,
    table,
    search: index.view.search,
    status: index.view.status,
    conditions: index.view.conditions,
    submit: index.view.submit,
    mode: index.view.mode,
    page,
    exporters,
    payloadActions,
    pagination,
    unfilteredTotal,
    loading: index.view.loading,
    selection,
    load,
    refresh: load,
    resetSearch,
    setInvalidElements,
  };
}
