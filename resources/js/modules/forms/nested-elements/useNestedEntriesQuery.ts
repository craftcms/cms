import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
import {runAction} from '@craftcms/ui/actions.mjs';
import {
  computed,
  nextTick,
  shallowRef,
  watch,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
import exportIndex from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ExportElementIndexController';
import {
  useDetachedElementIndex,
  type UseDetachedElementIndexOptions,
} from '@/modules/elements/index/composables/useDetachedElementIndex';
import type {
  IndexQueryParams,
  IndexQueryValue,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
import type {ElementIndexExportFormat} from '@/modules/elements/index/types/exporters';
import {
  markInvalidEntries,
  nestedEntriesErrorMessage,
  nestedIndexParams,
  type NestedContentIndexData,
  type NestedEntriesProps,
  type NestedIndexEntry,
} from './nested-entries';

type NestedColumns =
  UseDetachedElementIndexOptions<NestedContentIndexData>['columns'];

function normalizedSort(value: unknown): Array<{
  field: string;
  direction: 'asc' | 'desc';
}> {
  const items = Array.isArray(value)
    ? value
    : value instanceof Object
      ? Object.values(value)
      : [];

  return items.filter(
    (item): item is {field: string; direction: 'asc' | 'desc'} =>
      item instanceof Object &&
      !Array.isArray(item) &&
      typeof item.field === 'string' &&
      (item.direction === 'asc' || item.direction === 'desc') &&
      item.field !== 'score'
  );
}

async function appendPayloadAssets(
  payload: NestedContentIndexData
): Promise<void> {
  await appendHeadHtml(payload.headHtml ?? '');
  await appendBodyHtml(payload.bodyHtml ?? '');
}

export function useNestedEntriesQuery(options: {
  props: () => NestedEntriesProps;
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
  let loadEditable = false;
  const renderedMode = initial.viewState.mode;
  const renderedColumns = initial.defaultTableColumns.filter(
    (column) => column !== 'title'
  );
  const initialSort = normalizedSort(initial.sort);
  let preferredSort = initialSort;
  let appliedQuery: IndexQueryParams = {
    search: initial.search || undefined,
    status: initial.status || undefined,
    condition: (initial.currentCondition ?? undefined) as
      | IndexQueryValue
      | undefined,
    viewMode: renderedMode,
    columns: renderedColumns,
    sort: initialSort,
  };

  function completeQuery(query: IndexQueryParams): IndexQueryParams {
    return {
      viewMode: renderedMode,
      columns: renderedColumns,
      ...query,
      sort: normalizedSort(query.sort ?? initialSort),
    };
  }

  void appendPayloadAssets(initial).catch((cause) => {
    options.error.value = nestedEntriesErrorMessage(
      cause,
      t('Could not initialize nested entries.')
    );
  });

  const index = useDetachedElementIndex<NestedContentIndexData>({
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
    inlineEditing: options.saveInline
      ? {load: () => load(true), save: options.saveInline}
      : undefined,
    exportElements: exportEntries,
    rowReorder: options.rowReorder,
    pinnedColumn:
      props.index?.indexSettings?.showHeaderColumn === false
        ? null
        : {key: 'title', label: initial.elementDisplayName},
    data: (rows, mode, elementIndex) =>
      elementIndex.viewState.mode === mode ? rows : [],
    columns: options.columns,
    fetch: async (query) => {
      const currentManager = options.props().manager;
      if (!currentManager) {
        return initial;
      }

      const requestQuery = completeQuery(query);
      const {data} = await actionClient.post<NestedContentIndexData>(
        getElements.url(),
        {
          ...nestedIndexParams(
            currentManager,
            currentManager.ownerId,
            options.props().index?.initial
          ),
          elementType: currentManager.elementType,
          context: 'embeddedIndex',
          source: '__IMP__',
          ...requestQuery,
          ...(loadEditable && options.editable?.() ? {editable: true} : {}),
          ...(prevalidate.value ? {prevalidate: true} : {}),
          showInGrid: requestQuery.viewMode === 'cards',
        }
      );

      return data;
    },
    onLoaded: async (payload, query) => {
      appliedQuery = completeQuery(query);
      const confirmedSort = normalizedSort(appliedQuery.sort);
      if (confirmedSort[0]?.field !== 'score') {
        preferredSort = confirmedSort;
      }
      await nextTick();
      await appendPayloadAssets(payload);
      options.error.value = '';
      await options.onLoaded();
    },
    onError: (cause) => {
      options.error.value = nestedEntriesErrorMessage(
        cause,
        t('Could not load nested entries.')
      );
    },
  });

  index.restore();

  const {elementIndex, table, selection} = index.view;
  const entries = computed(() => index.view.data.value as NestedIndexEntry[]);
  const page = computed({
    get: () => table.getState().pagination.pageIndex + 1,
    set: (value: number) => table.setPageIndex(value - 1),
  });
  const pagination = computed(() => index.payload.value.pagination);
  const unfilteredTotal = computed(
    () => index.payload.value.pagination.unfilteredTotal ?? null
  );
  const payloadActions = computed(() => index.payload.value.actions);
  const exporters = computed(() => index.payload.value.exporters);

  async function load(inlineEditing = false): Promise<void> {
    loadEditable = inlineEditing;

    try {
      await index.load();
    } finally {
      loadEditable = false;
    }
  }

  async function exportEntries(
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
      await runAction({
        type: 'download',
        method: 'POST',
        url: exportIndex.url(),
        body: {
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
            ...(selectedIds.length ? {id: [...selectedIds]} : {}),
            ...(limit ? {limit} : {}),
          },
          sort: normalizedSort(appliedQuery.sort),
          condition: appliedQuery.condition ?? null,
          type,
          format,
        },
      });
    } catch (cause) {
      options.error.value = nestedEntriesErrorMessage(
        cause,
        t('Could not export nested entries.')
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
      window.Craft?.pageTrigger ?? 'page',
    ]);
    index.query.value.sort = preferredSort;

    await index.load();
  }

  function setInvalidEntries(ids: number[]): void {
    prevalidate.value = true;
    index.payload.value = {
      ...index.payload.value,
      data: markInvalidEntries(index.payload.value.data, ids),
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
    entries,
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
    setInvalidEntries,
  };
}
