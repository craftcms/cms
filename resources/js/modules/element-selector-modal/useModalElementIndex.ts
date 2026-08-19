import {actionClient} from '@craftcms/ui';
import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
import type {RowSelectionState} from '@tanstack/table-core';
import {computed, ref, watch} from 'vue';
import {useConditionBuilder} from '@/modules/elements/composables/useConditionBuilder';
import {
  useContentIndexData,
  type ContentIndexData,
} from '@/modules/elements/composables/useContentIndexData';
import {useElementIndexColumns} from '@/modules/elements/composables/useElementIndexColumns';
import {useElementIndexPagination} from '@/modules/elements/composables/useElementIndexPagination';
import {useElementIndexSort} from '@/modules/elements/composables/useElementIndexSort';
import {useElementIndexViewMode} from '@/modules/elements/composables/useElementIndexViewMode';
import {useElementIndexViewState} from '@/modules/elements/composables/useElementIndexViewState';
import {createModalIndexVisitor} from './modal-index-visitor';

type Row = Record<string, any>;

/** What the modal hands back for each selected row. */
export interface SelectedElement {
  id: number;
  siteId: number | null;
  label: string;
  status: string | null;
  url: string | null;
  hasThumb: boolean;
}

interface Options {
  /** The `element-selector-modals/body` endpoint. */
  url: string;
  /** The payload the modal opened with, so the first render needs no request. */
  initial: ContentIndexData;
  /** Params identifying the index — element type, sources, criteria, condition. */
  params: Record<string, unknown>;
  /** Element ids that can't be selected (already related, self-relation). */
  disabledElementIds?: () => number[];
}

/**
 * An element index driven from a modal rather than an Inertia page.
 *
 * Same composable stack the index screens use, with two substitutions: the
 * payload comes from a ref this refreshes over XHR instead of `usePage()`, and
 * navigation goes through {@link createModalIndexVisitor} instead of an Inertia
 * visit. Everything downstream — view state, filters, columns, sort, pagination
 * — is the page's code unchanged.
 *
 * Deliberately not `useElementIndexPage`: that one registers itself as the
 * active index for the whole screen, which would hijack the page behind the
 * modal.
 */
export function useModalElementIndex(options: Options) {
  const payload = ref<ContentIndexData>(options.initial);
  const loading = ref(false);

  async function load(query: Record<string, string>): Promise<void> {
    loading.value = true;

    try {
      const {data} = await actionClient.post(options.url, {
        ...options.params,
        ...query,
      });

      payload.value = data.props;
    } finally {
      loading.value = false;
    }
  }

  const visitor = createModalIndexVisitor(load);
  // The composables still want a route, but nothing navigates to it — every
  // request goes through the visitor above.
  const route = {url: () => options.url};

  const elementIndex = useContentIndexData(undefined, payload);
  const viewState = useElementIndexViewState(elementIndex);
  const {conditions} = useConditionBuilder({
    initialState: elementIndex.currentCondition ?? null,
  });
  // Not `useElementIndexFilters`: it submits through an Inertia form, which
  // would navigate the page behind the modal. Same params, sent the modal's way.
  const search = ref(elementIndex.search ?? '');
  const status = ref(elementIndex.status ?? '');

  watch([search, status, conditions], () => {
    visitor.merge(
      {
        search: search.value || null,
        status: status.value || null,
        condition: conditions.value ?? null,
      },
      {resetPage: true}
    );
  });
  const {columns, columnOrder, columnOptions, reorder, tableColumns} =
    useElementIndexColumns(
      elementIndex,
      viewState,
      {key: 'title', label: elementIndex.elementDisplayName},
      route,
      visitor
    );
  const {sortingState, sortingConfig, sortField, sortDirection} =
    useElementIndexSort(elementIndex, viewState, {route, visitor});
  const {paginationState, paginationConfig} = useElementIndexPagination(
    elementIndex,
    route,
    visitor
  );
  const {mode} = useElementIndexViewMode(route, viewState, visitor);

  const rowSelection = ref<RowSelectionState>({});
  const visibleColumns = ref({});

  const disabledIds = computed(
    () => new Set(options.disabledElementIds?.() ?? [])
  );

  const table = useVueTable<Row>({
    get data() {
      return elementIndex.data ?? [];
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnOrder() {
        return columnOrder.value;
      },
      get columnVisibility() {
        return visibleColumns.value;
      },
      get sorting() {
        return sortingState.value;
      },
      get pagination() {
        return paginationState.value;
      },
      get rowSelection() {
        return rowSelection.value;
      },
    },
    getRowId: (row) => String(row.id),
    // Folders navigate rather than select, and an element the field already
    // relates to can't be picked again.
    enableRowSelection: (row) =>
      !row.original?.isFolder &&
      !disabledIds.value.has(Number(row.original.id)),
    onRowSelectionChange: (updater) => {
      rowSelection.value =
        typeof updater === 'function' ? updater(rowSelection.value) : updater;
    },
    getCoreRowModel: getCoreRowModel<Row>(),
    ...sortingConfig,
    ...paginationConfig,
    enableMultiSort: false,
  });

  /**
   * The selected rows, in the shape the relation field expects.
   *
   * Mirrors what `Craft.getElementInfo()` read off each chip's data attributes,
   * because `onModalSelect` and `app/render-elements` both consume it.
   */
  const selectedElements = computed<SelectedElement[]>(() =>
    table.getSelectedRowModel().rows.map(({original}) => ({
      id: Number(original.id),
      siteId: original.siteId != null ? Number(original.siteId) : null,
      label: String(original.label ?? original.title ?? original.id),
      status: (original.status as string) ?? null,
      url: (original.url as string) ?? null,
      hasThumb: Boolean(original.hasThumb ?? original.thumb),
    }))
  );

  const hasSelection = computed(() => selectedElements.value.length > 0);

  function clearSelection(): void {
    rowSelection.value = {};
  }

  return {
    elementIndex,
    table,
    viewState,
    conditions,
    search,
    status,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    loading,
    rowSelection,
    selectedElements,
    hasSelection,
    clearSelection,
    load,
    query: visitor.query,
  };
}
