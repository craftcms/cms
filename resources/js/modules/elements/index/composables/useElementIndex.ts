import {
  getCoreRowModel,
  useVueTable,
  type ColumnDef,
} from '@tanstack/vue-table';
import type {RowSelectionState} from '@tanstack/table-core';
import {
  computed,
  shallowRef,
  toValue,
  watch,
  type ComputedRef,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import type {
  useContentIndexData,
  ElementIndexRow,
} from '@/modules/elements/index/composables/useContentIndexData';
import {useElementIndexColumns} from '@/modules/elements/index/composables/useElementIndexColumns';
import {useElementIndexFilters} from '@/modules/elements/index/composables/useElementIndexFilters';
import {useElementIndexPagination} from '@/modules/elements/index/composables/useElementIndexPagination';
import {useElementIndexSort} from '@/modules/elements/index/composables/useElementIndexSort';
import {
  cascadeStructureSelection,
  deselectDescendants,
  selectDescendantsOfSelected,
  useElementIndexStructure,
} from '@/modules/elements/index/composables/useElementIndexStructure';
import {useElementIndexViewMode} from '@/modules/elements/index/composables/useElementIndexViewMode';
import {useElementIndexViewState} from '@/modules/elements/index/composables/useElementIndexViewState';
import type {
  IndexQueryParams,
  IndexRestore,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useElementIndexSelection} from '@/modules/elements/index/composables/useElementIndexSelection';
import type {
  ElementIndexModel,
  ElementIndexView,
  ExportElementIndex,
} from '@/modules/elements/index/types/model';
import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
import type {ElementIndexContext} from '@/modules/elements/index/index-context';
import type {ViewMode} from '@/modules/elements/types/view-state';

interface UseElementIndexOptions {
  elementIndex: ReturnType<typeof useContentIndexData>;
  visitor: IndexVisitor;
  loading: Ref<boolean>;
  pinnedColumn?: {key: string; label: string} | null;
  storageKey?: string;
  busy?: MaybeRefOrGetter<boolean>;
  filterParams?: () => IndexQueryParams;
  filterContext?: () => Pick<
    ElementIndexContext,
    'fieldLayouts' | 'extraParams'
  >;
  inlineEditing?: {
    load(): Promise<void>;
    save(body: URLSearchParams): Promise<InlineEditingSaveResult | false>;
  };
  exportElements?: ExportElementIndex;
  rowReorder?: {
    enabled: MaybeRefOrGetter<boolean>;
    move(from: number, to: number): void | Promise<void>;
  };
  enableRowSelection?: (row: ElementIndexRow) => boolean;
  data?: (
    rows: ElementIndexRow[],
    mode: ViewMode['mode'],
    elementIndex: ReturnType<typeof useContentIndexData>
  ) => ElementIndexRow[];
  columns?: (
    columns: ComputedRef<Array<ColumnDef<ElementIndexRow>>>,
    context: {elementIndex: ReturnType<typeof useContentIndexData>}
  ) => ComputedRef<Array<ColumnDef<ElementIndexRow>>>;
  structure?: boolean;
  readOnly?: MaybeRefOrGetter<boolean>;
  refresh: () => Promise<boolean>;
}

/** Shared state and table setup for page and detached element indexes. */
export function useElementIndex(options: UseElementIndexOptions) {
  const {elementIndex, visitor, loading} = options;
  const viewState = useElementIndexViewState(elementIndex, options.storageKey);
  const initialSourceKey = elementIndex.source?.key ?? '*';
  const preferredSort = elementIndex.sort.filter(
    ({field}) => field !== 'score'
  );
  const filters = useElementIndexFilters(visitor, {
    search: elementIndex.search,
    status: elementIndex.status,
    conditions: elementIndex.currentCondition,
    busy: options.busy,
    preferredSort: () => {
      const sourceKey = elementIndex.source?.key ?? '*';
      const saved = viewState.value.sources?.[sourceKey]?.sort?.filter(
        ({field}) => field !== 'score'
      );

      if (saved?.length) {
        return saved;
      }

      return sourceKey === initialSourceKey ? preferredSort : [];
    },
    params: () => ({
      source: elementIndex.source?.key,
      viewMode: viewState.value.mode,
      ...options.filterParams?.(),
    }),
  });
  const pinnedColumn =
    options.pinnedColumn === undefined
      ? {key: 'title', label: elementIndex.elementDisplayName}
      : options.pinnedColumn;
  const {
    columns: baseColumns,
    columnOrder,
    columnOptions,
    tableColumns,
    reorder,
    restore: restoreColumns,
  } = useElementIndexColumns(elementIndex, viewState, pinnedColumn, visitor);
  const columns = options.columns?.(baseColumns, {elementIndex}) ?? baseColumns;
  const {
    sortingState,
    sortingConfig,
    sortField,
    sortDirection,
    restore: restoreSort,
  } = useElementIndexSort(elementIndex, viewState, visitor);
  const {paginationState, paginationConfig} = useElementIndexPagination(
    elementIndex,
    visitor
  );
  const {mode: savedMode, restore: restoreViewMode} = useElementIndexViewMode(
    viewState,
    visitor
  );
  const visibleViewModes = computed(() =>
    elementIndex.viewModes.filter(
      (viewMode) =>
        !viewMode.structuresOnly || elementIndex.source?.structureId != null
    )
  );
  const mode = computed<ViewMode['mode']>({
    get: () =>
      visibleViewModes.value.some(({mode}) => mode === savedMode.value)
        ? savedMode.value
        : (visibleViewModes.value[0]?.mode ?? 'table'),
    set: (value) => {
      savedMode.value = value;
    },
  });
  const inlineEditingActive = computed({
    get: () => viewState.value.inlineEditing,
    set: (value: boolean) => {
      viewState.value.inlineEditing = value;
    },
  });
  const structureView = useElementIndexStructure(
    elementIndex,
    viewState,
    visitor
  );
  const data = computed(() =>
    options.data
      ? options.data(elementIndex.data ?? [], mode.value, elementIndex)
      : options.structure
        ? structureView.visibleRows(elementIndex.data ?? [])
        : (elementIndex.data ?? [])
  );
  const rowSelection = shallowRef<RowSelectionState>({});

  watch(data, (rows) => {
    if (options.structure && structureView.isStructure.value) {
      rowSelection.value = selectDescendantsOfSelected(
        rowSelection.value,
        rows
      );
    }
  });

  function toggleStructure(id: string | number): void {
    if (!structureView.isCollapsed(id)) {
      rowSelection.value = deselectDescendants(
        rowSelection.value,
        data.value,
        id
      );
    }

    structureView.toggle(id);
  }

  const table = useVueTable<ElementIndexRow>({
    get data() {
      return data.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnOrder() {
        return columnOrder.value;
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
    enableRowSelection: (row) =>
      options.enableRowSelection?.(row.original) ?? true,
    onRowSelectionChange: (updater) => {
      const next =
        typeof updater === 'function' ? updater(rowSelection.value) : updater;
      rowSelection.value =
        options.structure && structureView.isStructure.value
          ? cascadeStructureSelection(rowSelection.value, next, data.value)
          : next;
    },
    getCoreRowModel: getCoreRowModel<ElementIndexRow>(),
    ...sortingConfig,
    ...paginationConfig,
    enableMultiSort: false,
  });

  function restore(): void {
    const restores = [
      restoreColumns(),
      restoreSort(),
      restoreViewMode(),
      ...(options.structure ? [structureView.restore()] : []),
    ].filter((restore): restore is IndexRestore => restore !== null);

    if (restores.length === 0) {
      return;
    }

    visitor.merge(
      Object.assign({}, ...restores.map((restore) => restore.params)),
      {
        only: [...new Set(restores.flatMap((restore) => restore.only))],
        replace: true,
      }
    );
  }

  const selection = useElementIndexSelection(table, {
    selectable: true,
    readOnly: options.readOnly ?? false,
  });

  watch(
    () => elementIndex.source?.key,
    () => selection.clearSelection()
  );
  watch(data, (rows, previous) => {
    if (
      rows.length !== previous.length ||
      rows.some((row, index) => row.id !== previous[index]?.id)
    )
      selection.selection.prune();
  });

  function changeSource(key: string): void {
    if (key === elementIndex.source?.key) return;
    visitor.merge({source: key, viewMode: mode.value}, {resetPage: true});
  }

  function changeSite(handle: string): void {
    selection.clearSelection();
    visitor.merge({site: handle}, {resetPage: true});
  }

  async function refresh(refreshOptions?: {selectId?: number}): Promise<void> {
    const applied = await options.refresh();
    if (!applied || refreshOptions?.selectId === undefined) return;
    const row = table
      .getRowModel()
      .rows.find(({original}) => original.id === refreshOptions.selectId);
    if (row) selection.selectRow(row, {checked: true});
  }

  const processing = computed(
    () => loading.value || Boolean(toValue(options.busy))
  );
  const filterContext = computed<ElementIndexContext | null>(() =>
    elementIndex.source
      ? {
          elementType: elementIndex.elementType,
          context: elementIndex.context,
          source: elementIndex.source,
          ...options.filterContext?.(),
        }
      : null
  );
  const view: ElementIndexView = {
    elementIndex,
    table,
    data,
    selection,
    search: filters.search,
    status: filters.status,
    conditions: filters.conditions,
    ...(options.inlineEditing
      ? {
          inlineEditing: {
            active: inlineEditingActive,
            ...options.inlineEditing,
          },
        }
      : {}),
    ...(options.exportElements ? {exportElements: options.exportElements} : {}),
    ...(options.rowReorder
      ? {
          rowReorder: {
            enabled: computed(() =>
              Boolean(toValue(options.rowReorder!.enabled))
            ),
            move: (from, to) => options.rowReorder!.move(from, to),
          },
        }
      : {}),
    submit: filters.submit,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    loading,
    processing,
    filterContext,
    visibleViewModes,
    structureView,
    toggleStructure,
  };
  const model: ElementIndexModel = {
    view,
    changeSource,
    changeSite,
    refresh,
    clearSelection: selection.clearSelection,
    findRow: (id) => data.value.find((row) => String(row.id) === String(id)),
    captureSelection: () => {
      const previous = {...rowSelection.value};
      const anchor = selection.anchorIndex.value;
      return () => {
        rowSelection.value = previous;
        selection.anchorIndex.value = anchor;
      };
    },
  };

  return {model, restore, cancelPendingSearch: filters.cancelPendingSearch};
}
