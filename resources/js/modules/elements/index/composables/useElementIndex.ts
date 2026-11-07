import type {ColumnDef, RowSelectionState} from '@tanstack/vue-table';
import {
  type CraftTableFeatures,
  useCraftTable,
} from '@/common/table/craftTable';
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
import {useElementIndexThumbSize} from '@/modules/elements/index/composables/useElementIndexThumbSize';
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
import {onElementChanged} from '@/modules/elements/element-instances';
import {tryOnScopeDispose} from '@vueuse/core';
import {useElementIndexViewState} from '@/modules/elements/index/composables/useElementIndexViewState';
import type {
  IndexQueryParams,
  IndexRestore,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
import type {
  ElementIndexModel,
  ElementIndexSelectAll,
  ElementIndexView,
  ExportElementIndex,
} from '@/modules/elements/index/types/model';
import type {InlineEditingSaveResult} from '@/modules/elements/index/composables/useInlineEditing';
import type {ElementIndexContext} from '@/modules/elements/index/index-context';
import type {
  CardThumbSettings,
  ViewMode,
} from '@/modules/elements/types/view-state';

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
  /** Whether more than one row can be selected at a time. Defaults to true. */
  multiSelect?: boolean;
  data?: (
    rows: ElementIndexRow[],
    mode: ViewMode['mode'],
    elementIndex: ReturnType<typeof useContentIndexData>
  ) => ElementIndexRow[];
  columns?: (
    columns: ComputedRef<Array<ColumnDef<CraftTableFeatures, ElementIndexRow>>>,
    context: {elementIndex: ReturnType<typeof useContentIndexData>}
  ) => ComputedRef<Array<ColumnDef<CraftTableFeatures, ElementIndexRow>>>;
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
  const sourceKey = () => elementIndex.source?.key ?? '*';
  const {
    mode: savedMode,
    restore: restoreViewMode,
    persistedMode,
  } = useElementIndexViewMode(
    viewState,
    visitor,
    sourceKey,
    () => elementIndex.viewState?.mode,
    () => visibleViewModes.value.map(({mode}) => mode),
    (mode) => thumbSizeModeParams(mode)
  );
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
    isDefault: columnsAreDefault,
    resetToDefault: resetColumns,
  } = useElementIndexColumns(elementIndex, viewState, pinnedColumn, visitor);
  const columns = options.columns?.(baseColumns, {elementIndex}) ?? baseColumns;
  const {
    sortingState,
    sortingConfig,
    sortField,
    sortDirection,
    restore: restoreSort,
    isDefault: sortIsDefault,
    resetToDefault: resetSort,
  } = useElementIndexSort(elementIndex, viewState, visitor);
  const {paginationState, paginationConfig} = useElementIndexPagination(
    elementIndex,
    visitor
  );
  // The selection watcher below is sync, so it reruns this when a visit swaps
  // in the next page's props, before the index unmounts. Throwing there would
  // reject Inertia's swap, which then skips loading the next page's deferred
  // props.
  const visibleViewModes = computed(() =>
    (elementIndex.viewModes ?? []).filter(
      (viewMode) =>
        !viewMode.structuresOnly || elementIndex.source?.structureId != null
    )
  );
  /**
   * How cards show a thumbnail, as the source's field layout has it, or null
   * when the source's elements have no thumbnails.
   */
  const cardThumbs = computed<CardThumbSettings | null>(
    () => elementIndex.cardThumbs ?? null
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
  const filters = useElementIndexFilters(visitor, {
    search: elementIndex.search,
    status: elementIndex.statusMenuValue,
    conditions: elementIndex.currentCondition,
    busy: options.busy,
    preferredSort: () => {
      const saved = viewState.value.sources?.[sourceKey()]?.sort?.filter(
        ({field}) => field !== 'score'
      );

      if (saved?.length) {
        return saved;
      }

      return sourceKey() === initialSourceKey ? preferredSort : [];
    },
    params: () => ({
      source: elementIndex.source?.key,
      viewMode: mode.value,
      ...options.filterParams?.(),
    }),
  });
  const {
    thumbSize,
    options: thumbSizeOptions,
    restore: restoreThumbSize,
    modeParams: thumbSizeModeParams,
    isDefault: thumbSizeIsDefault,
    resetToDefault: resetThumbSize,
  } = useElementIndexThumbSize(viewState, visitor, () => mode.value, sourceKey);
  // The View menu only offers table columns in table and structure views, so
  // only there do they count toward, or get reset by, "Use defaults".
  const showsTableColumns = () =>
    mode.value === 'table' || mode.value === 'structure';
  const viewCustomized = computed(
    () =>
      !sortIsDefault.value ||
      !thumbSizeIsDefault.value ||
      (showsTableColumns() && !columnsAreDefault.value)
  );

  /** Returns the View menu's settings to the source's defaults in one visit. */
  function resetView(): void {
    const resets = [
      resetSort(),
      showsTableColumns() ? resetColumns() : null,
      resetThumbSize(),
    ].filter((reset): reset is IndexRestore => reset !== null);

    if (resets.length === 0) {
      return;
    }

    visitor.merge(Object.assign({}, ...resets.map((reset) => reset.params)), {
      only: [...new Set(resets.flatMap((reset) => reset.only))],
      resetPage: true,
    });
  }

  // Not persisted: inline editing ends with the page.
  const inlineEditingActive = shallowRef(false);
  const structureView = useElementIndexStructure(
    elementIndex,
    viewState,
    visitor,
    () => mode.value
  );
  const data = computed(() =>
    options.data
      ? options.data(elementIndex.data ?? [], mode.value, elementIndex)
      : options.structure
        ? structureView.visibleRows(elementIndex.data ?? [])
        : (elementIndex.data ?? [])
  );
  const rowSelection = shallowRef<RowSelectionState>({});
  /**
   * Whether every element the index lists is selected, including those on
   * pages that haven't been loaded, other than any deselected since.
   */
  const selectsAll = shallowRef(false);
  const deselectedIds = shallowRef<ReadonlySet<string>>(new Set());

  function selectRows(rows: ElementIndexRow[], selection = rowSelection.value) {
    const next = {...selection};

    for (const row of rows) {
      const id = String(row.id);
      if (
        !deselectedIds.value.has(id) &&
        (options.enableRowSelection?.(row) ?? true)
      ) {
        next[id] = true;
      }
    }

    return next;
  }

  watch(data, (rows) => {
    if (selectsAll.value) {
      rowSelection.value = selectRows(rows);
    }

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

  const table = useCraftTable<ElementIndexRow>({
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
    // Off, selecting a row deselects the others.
    enableMultiRowSelection: options.multiSelect ?? true,
    onRowSelectionChange: (updater) => {
      const next =
        typeof updater === 'function' ? updater(rowSelection.value) : updater;
      rowSelection.value =
        options.structure && structureView.isStructure.value
          ? cascadeStructureSelection(rowSelection.value, next, data.value)
          : next;

      // Deselecting the whole page, as the select-all checkbox does, ends
      // the selection of every result, not just this page's share of it.
      if (
        selectsAll.value &&
        !data.value.some((row) => rowSelection.value[String(row.id)])
      ) {
        selectsAll.value = false;
        deselectedIds.value = new Set();
        rowSelection.value = {};
      }

      if (selectsAll.value) {
        const deselected = new Set(deselectedIds.value);

        for (const row of data.value) {
          const id = String(row.id);
          if (rowSelection.value[id]) {
            deselected.delete(id);
          } else {
            deselected.add(id);
          }
        }

        deselectedIds.value = deselected;
      }
    },
    ...sortingConfig,
    ...paginationConfig,
    // A getter, after the spread: the spread copies the total the index was
    // created with, which every later source or filter would keep showing as
    // its page count.
    get rowCount() {
      return elementIndex.pagination?.total ?? 0;
    },
    enableMultiSort: false,
  });

  /**
   * Whether the rows are still to come. A page load leaves them out until it
   * knows the view mode and thumbnail size to render them for; see `restore()`.
   */
  const pending = computed(() => elementIndex.data === undefined);

  function restore(): void {
    const restores = [
      restoreColumns(),
      restoreSort(),
      restoreViewMode(),
      restoreThumbSize(),
      ...(options.structure ? [structureView.restore()] : []),
    ].filter((restore): restore is IndexRestore => restore !== null);

    // Requested after the others have read the URL into the view state, so the
    // mode and size are the ones the index will show. Naming the mode in the
    // URL also gets the rows rendered along with any later page load. `queue`
    // reports the thumbnail jobs rendering them queued.
    if (pending.value) {
      restores.unshift({
        params: {viewMode: mode.value, ...thumbSizeModeParams(mode.value)},
        only: ['data', 'pagination', 'structure', 'queue'],
      });
    }

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

  /**
   * The selected rows on every page, by row id. The table only holds the page
   * on screen, so rows selected on other pages are remembered as they were
   * when last shown.
   */
  const selectedRowsById = shallowRef(new Map<string, ElementIndexRow>());

  watch(
    [rowSelection, data],
    ([selected, rows]) => {
      const shown = new Map(rows.map((row) => [String(row.id), row]));
      const next = new Map<string, ElementIndexRow>();

      for (const [id, isSelected] of Object.entries(selected)) {
        const row = isSelected
          ? (shown.get(id) ?? selectedRowsById.value.get(id))
          : undefined;

        if (row) next.set(id, row);
      }

      selectedRowsById.value = next;
    },
    {immediate: true, flush: 'sync'}
  );

  const selection = useTableRowSelection(table, {
    selectable: true,
    readOnly: options.readOnly ?? false,
    selectedRows: () => [...selectedRowsById.value.values()],
    onClear: () => {
      selectsAll.value = false;
      deselectedIds.value = new Set();
    },
  });

  const selectAll: ElementIndexSelectAll = {
    active: selectsAll,
    exceptIds: computed(() => [...deselectedIds.value]),
    count: computed(() =>
      selectsAll.value
        ? Math.max(
            0,
            (elementIndex.pagination?.total ?? 0) - deselectedIds.value.size
          )
        : selection.selectedIds.value.length
    ),
    selectAll: () => {
      deselectedIds.value = new Set();
      selectsAll.value = true;
      rowSelection.value = selectRows(data.value);
    },
    selectPage: () => {
      selection.clearSelection();
      rowSelection.value = selectRows(data.value);
    },
  };

  // A selection belongs to the elements the index lists, so it's dropped when
  // they change, but kept across pages and sort orders. With every result
  // selected, the server would otherwise act on whatever the new search or
  // filters list.
  watch(
    () =>
      JSON.stringify([
        elementIndex.source?.key,
        elementIndex.siteId,
        elementIndex.search,
        elementIndex.status,
        elementIndex.currentCondition,
        elementIndex.drafts,
        elementIndex.trashed,
      ]),
    () => selection.clearSelection()
  );

  const listing = () =>
    JSON.stringify([
      elementIndex.pagination?.current_page,
      elementIndex.pagination?.per_page,
      elementIndex.sort,
    ]);
  let previousListing = listing();

  watch(data, (rows, previous) => {
    const currentListing = listing();
    const samePage = currentListing === previousListing;
    previousListing = currentListing;

    if (
      rows.length === previous.length &&
      rows.every((row, index) => row.id === previous[index]?.id)
    )
      return;

    selection.anchorIndex.value = null;

    // When the same page reloads, rows that left it are gone, not on another
    // page, so they leave the selection too.
    if (!samePage || pending.value) return;

    const kept = new Set(rows.map((row) => String(row.id)));
    const departed = new Set(
      previous
        .map((row) => String(row.id))
        .filter((id) => !kept.has(id) && rowSelection.value[id])
    );

    if (departed.size) {
      rowSelection.value = Object.fromEntries(
        Object.entries(rowSelection.value).filter(([id]) => !departed.has(id))
      );
    }
  });

  function changeSource(key: string): void {
    if (key === elementIndex.source?.key) return;
    const sourceMode = persistedMode(key);

    // A source without a chosen mode gets its default, which only the server
    // knows, so its thumbnail size can't be asked for until its rows arrive.
    visitor.merge(
      {
        source: key,
        viewMode: sourceMode ?? null,
        ...(sourceMode
          ? thumbSizeModeParams(sourceMode, key)
          : {thumbSize: null}),
      },
      {
        resetPage: true,
        onFinish: ({completed}) => {
          const thumbSizeRestore =
            completed && !sourceMode && restoreThumbSize();

          if (thumbSizeRestore) {
            visitor.merge(thumbSizeRestore.params, {
              only: thumbSizeRestore.only,
              replace: true,
            });
          }
        },
      }
    );
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

  // Rows saved in another tab are reloaded, once for all the rows saved
  // together. Saves in this one refresh the index where they happen, such as
  // after a quick edit. While rows are edited inline, the reload waits until
  // editing ends, so it doesn't redraw the inputs over unsaved changes.
  let reloadQueued = false;
  let reloadAfterEditing = false;
  tryOnScopeDispose(
    onElementChanged((id) => {
      const shown =
        id === null ||
        elementIndex.data?.some((row) => Number(row.id) === id) === true;
      if (!shown || reloadQueued) return;
      if (inlineEditingActive.value) {
        reloadAfterEditing = true;
        return;
      }
      reloadQueued = true;
      queueMicrotask(() => {
        reloadQueued = false;
        void refresh();
      });
    })
  );
  watch(inlineEditingActive, (active) => {
    if (active || !reloadAfterEditing) return;
    reloadAfterEditing = false;
    void refresh();
  });

  const processing = computed(
    () => loading.value || pending.value || Boolean(toValue(options.busy))
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
    selectAll,
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
    viewCustomized,
    resetView,
    mode,
    thumbSize,
    thumbSizeOptions,
    cardThumbs,
    loading,
    pending,
    processing,
    cancelPageLoad: () => visitor.cancel?.(),
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
