import {
  createSortedRowModel,
  tableFeatures,
  useTable,
  type Row,
  type RowSelectionState,
  type SortingState,
  type Updater,
} from '@tanstack/vue-table';
import {watchDebounced} from '@vueuse/core';
import {computed, shallowRef, watch} from 'vue';
import {t} from '@craftcms/ui';
import {
  craftTableFeatures,
  type CraftTableFeatures,
} from '@/common/table/craftTable';
import {useOptionalLocalStorage} from '@/common/composables/useStorage';
import {usePaginatedRows} from '@/common/composables/usePaginatedRows';
import type {CheckboxOption} from '@/common/types';
import type {SortOption} from '@/modules/elements/types/view-state';
import type {
  AdminTableDataOptions,
  AdminTableReorder,
  AdminTableRequest,
} from './types';

export function useAdminTable<TData extends Record<string, any>>(
  options: AdminTableDataOptions<TData>,
  onLoadError: (error: unknown) => void
) {
  function preference<T>(key: string, initial: T) {
    return useOptionalLocalStorage(
      options.storageKey ? `${options.storageKey}.${key}` : undefined,
      initial
    );
  }

  const rows = shallowRef([...options.rows]);
  watch(
    () => options.rows,
    (value) => {
      rows.value = [...value];
      table.resetRowSelection();
    }
  );

  const paginated = computed(() => !!options.loadRows);
  const pageData = usePaginatedRows<TData, AdminTableRequest>(
    () => options.loadRows,
    onLoadError
  );
  const {rows: pageRows, pagination, loading} = pageData;
  const reordering = shallowRef(false);
  const search = shallowRef('');
  const storedPageSize = preference('perPage', options.pageSize);
  const pageSize = shallowRef(
    paginated.value && options.pageSizeOptions.includes(storedPageSize.value)
      ? storedPageSize.value
      : options.pageSize
  );
  const storedStatus = preference('status', '');
  const status = shallowRef(
    options.statusFilterOptions.some(
      (option) => option.value === storedStatus.value
    )
      ? storedStatus.value
      : ''
  );
  const storedSort = preference<SortingState>('sort', []);
  const sorting = shallowRef<SortingState>(
    (storedSort.value ?? [])
      .filter((sort) =>
        options.columns.some(
          (column) =>
            (column.id ??
              ('accessorKey' in column ? column.accessorKey : undefined)) ===
              sort.id && column.enableSorting
        )
      )
      .slice(0, 1)
  );
  const rowSelection = shallowRef<RowSelectionState>({});

  const viewColumns = computed(() =>
    options.columns.flatMap((column) => {
      const key =
        column.id ??
        ('accessorKey' in column ? String(column.accessorKey) : undefined);
      return key && typeof column.header === 'string' && column.header
        ? [{key, label: column.header, sortable: !!column.enableSorting}]
        : [];
    })
  );
  const pinnedColumn = computed(() => viewColumns.value[0]);
  const toggleableKeys = computed(() =>
    viewColumns.value.slice(1).map((column) => column.key)
  );
  const storedColumns = preference<{
    visible: string[];
    hidden: string[];
  } | null>('columns', null);
  const initialColumns = options.columnsToggleable ? storedColumns.value : null;
  const known = new Set([
    ...(initialColumns?.visible ?? []),
    ...(initialColumns?.hidden ?? []),
  ]);
  const visibleColumns = shallowRef([
    ...(initialColumns?.visible ?? []).filter((key) =>
      toggleableKeys.value.includes(key)
    ),
    ...toggleableKeys.value.filter(
      (key) => !known.has(key) && !options.hiddenColumnsByDefault.includes(key)
    ),
  ]);

  function saveVisibleColumns(visible: string[]): void {
    visibleColumns.value = visible;
    storedColumns.value = {
      visible,
      hidden: toggleableKeys.value.filter((key) => !visible.includes(key)),
    };
  }

  const viewTableColumns = computed({
    get: () => visibleColumns.value,
    set: (value: string[]) => {
      const next = [
        ...visibleColumns.value.filter((key) => value.includes(key)),
        ...value.filter(
          (key) =>
            key !== pinnedColumn.value?.key &&
            !visibleColumns.value.includes(key)
        ),
      ];
      if (
        next.length !== visibleColumns.value.length ||
        next.some((key, index) => key !== visibleColumns.value[index])
      ) {
        saveVisibleColumns(next);
      }
    },
  });

  function onColumnsReorder(columns: CheckboxOption[]): void {
    saveVisibleColumns(
      columns
        .map((column) => String(column.value))
        .filter((key) => visibleColumns.value.includes(key))
    );
  }

  const viewColumnOptions = computed<CheckboxOption[]>(() => {
    const labels = new Map(
      viewColumns.value.map((column) => [column.key, column.label])
    );
    return [
      ...(pinnedColumn.value
        ? [
            {
              label: pinnedColumn.value.label,
              value: pinnedColumn.value.key,
              disabled: true,
              checked: true,
            },
          ]
        : []),
      ...visibleColumns.value.map((key) => ({
        label: labels.get(key) ?? key,
        value: key,
      })),
      ...toggleableKeys.value
        .filter((key) => !visibleColumns.value.includes(key))
        .map((key) => ({label: labels.get(key) ?? key, value: key}))
        .sort((a, b) => a.label.localeCompare(b.label)),
    ];
  });

  const viewSortOptions = computed<SortOption[]>(() => {
    const columns = viewColumns.value.filter((column) => column.sortable);
    return columns.length
      ? [
          {
            label: options.reorderable ? t('Manual order') : t('Default order'),
            value: '',
            defaultDir: 'asc',
          },
          ...columns.map((column) => ({
            label: column.label,
            value: column.key,
            defaultDir: 'asc' as const,
          })),
        ]
      : [];
  });

  function onSortingChange(updater: Updater<SortingState>): void {
    sorting.value =
      typeof updater === 'function' ? updater(sorting.value) : updater;
    storedSort.value = sorting.value;
    table.resetRowSelection();
    if (paginated.value) void refresh(1);
  }

  const viewSortField = computed({
    get: () => sorting.value[0]?.id ?? '',
    set: (field: string) =>
      onSortingChange(
        field ? [{id: field, desc: sorting.value[0]?.desc ?? false}] : []
      ),
  });
  const viewSortDirection = computed({
    get: (): 'asc' | 'desc' => (sorting.value[0]?.desc ? 'desc' : 'asc'),
    set: (direction: 'asc' | 'desc') => {
      if (sorting.value[0])
        onSortingChange([
          {id: sorting.value[0].id, desc: direction === 'desc'},
        ]);
    },
  });
  const orderingDisabled = computed(
    () =>
      !!search.value ||
      !!status.value ||
      sorting.value.length > 0 ||
      reordering.value
  );
  const filteredRows = computed(() => {
    const query = search.value.trim().toLowerCase();
    return rows.value.filter(
      (row) =>
        (!status.value || options.getRowStatus?.(row) === status.value) &&
        (!query ||
          (options.getSearchText?.(row) ?? Object.values(row).join(' '))
            .toLowerCase()
            .includes(query))
    );
  });
  const displayedRows = computed(() =>
    paginated.value ? pageRows.value : filteredRows.value
  );
  const paginationState = computed(() => ({
    pageIndex: (pagination.value?.current_page ?? 1) - 1,
    pageSize: pageSize.value,
  }));
  const collator = new Intl.Collator(undefined, {
    numeric: true,
    sensitivity: 'base',
  });
  function sortText(value: unknown): string {
    return typeof value === 'string' ||
      typeof value === 'number' ||
      typeof value === 'boolean'
      ? String(value)
      : '';
  }

  const columns = computed(() =>
    options.columns.map((column) => ({
      ...column,
      sortFn:
        column.sortFn ??
        ((
          a: Row<CraftTableFeatures, TData>,
          b: Row<CraftTableFeatures, TData>,
          key: string
        ) => {
          const left = options.getSortValue
            ? options.getSortValue(a.original, key)
            : a.getValue(key);
          const right = options.getSortValue
            ? options.getSortValue(b.original, key)
            : b.getValue(key);
          return typeof left === 'number' && typeof right === 'number'
            ? left - right
            : collator.compare(sortText(left), sortText(right));
        }),
    }))
  );

  function onPaginationChange(
    updater: Updater<{pageIndex: number; pageSize: number}>
  ): void {
    if (!paginated.value) return;
    const next =
      typeof updater === 'function' ? updater(paginationState.value) : updater;
    const previous = pageSize.value;
    pageSize.value = next.pageSize;
    table.resetRowSelection();
    const pending = refresh(
      next.pageSize !== previous ? 1 : next.pageIndex + 1
    );
    const paginationRequest = pageData.request.value;
    void pending.then((success) => {
      if (pageData.request.value !== paginationRequest) return;
      if (success) storedPageSize.value = pageSize.value;
      else pageSize.value = previous;
    });
  }

  const features = tableFeatures({
    ...craftTableFeatures,
    sortedRowModel: createSortedRowModel(),
  });
  const table = useTable<CraftTableFeatures, TData>({
    features,
    get data() {
      return displayedRows.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get rowSelection() {
        return rowSelection.value;
      },
      get pagination() {
        return paginationState.value;
      },
      get sorting() {
        return sorting.value;
      },
      get columnVisibility() {
        return options.columnsToggleable
          ? Object.fromEntries(
              toggleableKeys.value.map((key) => [
                key,
                visibleColumns.value.includes(key),
              ])
            )
          : {};
      },
      get columnOrder() {
        return options.columnsToggleable && pinnedColumn.value
          ? [pinnedColumn.value.key, ...visibleColumns.value]
          : [];
      },
    },
    getRowId: (row, index) => String(row.id ?? `row-${index}`),
    enableRowSelection: (row) =>
      options.selectable && (options.canSelectRow?.(row.original) ?? true),
    onRowSelectionChange: (updater) => {
      rowSelection.value =
        typeof updater === 'function' ? updater(rowSelection.value) : updater;
    },
    enableMultiSort: false,
    sortDescFirst: false,
    get manualSorting() {
      return paginated.value;
    },
    onSortingChange,
    get manualPagination() {
      return paginated.value;
    },
    get pageCount() {
      return paginated.value ? (pagination.value?.last_page ?? -1) : -1;
    },
    onPaginationChange,
  });

  async function refresh(
    page = pagination.value?.current_page ?? 1
  ): Promise<boolean> {
    if (!options.loadRows) return true;

    const pending = pageData.load({
      page,
      perPage: pageSize.value,
      search: search.value || undefined,
      status: status.value || undefined,
      sort: sorting.value.length
        ? sorting.value.map((sort) => ({
            field: sort.id,
            direction: sort.desc ? 'desc' : 'asc',
          }))
        : undefined,
    });
    const request = pageData.request.value;
    const success = await pending;
    const result = pagination.value;
    if (!success || request !== pageData.request.value || !result) return false;

    pageSize.value = result.per_page;
    if (!pageRows.value.length && page > 1 && page > result.last_page) {
      return refresh(Math.max(1, result.last_page));
    }
    return true;
  }

  watch(status, (value) => {
    storedStatus.value = value;
    table.resetRowSelection();
    if (paginated.value) void refresh(1);
  });
  watch(search, () => table.resetRowSelection());
  watchDebounced(
    search,
    () => {
      if (paginated.value) void refresh(1);
    },
    {debounce: 300}
  );
  watch(
    () => options.loadRows,
    () => {
      if (paginated.value) void refresh(1);
    },
    {immediate: true}
  );

  function removeRows(ids: Array<string | number>): void {
    rows.value = rows.value.filter((row) => !ids.includes(row.id));
    pageRows.value = pageRows.value.filter((row) => !ids.includes(row.id));
    table.resetRowSelection();
  }

  async function reorder(
    startIndex: number,
    finishIndex: number,
    perform: (move: AdminTableReorder<TData>) => Promise<void>
  ): Promise<void> {
    if (orderingDisabled.value || loading.value) return;
    const target = paginated.value ? pageRows : rows;
    const previous = target.value;
    const reordered = [...previous];
    const [row] = reordered.splice(startIndex, 1);
    if (!row || finishIndex < 0 || finishIndex >= previous.length) return;
    reordered.splice(finishIndex, 0, row);
    target.value = reordered;
    reordering.value = true;
    try {
      await perform({
        row,
        rows: reordered,
        startIndex,
        finishIndex,
        page: paginationState.value.pageIndex + 1,
        pageSize: pageSize.value,
        paginated: paginated.value,
      });
      if (paginated.value) await refresh();
    } catch (error) {
      target.value = previous;
      throw error;
    } finally {
      reordering.value = false;
    }
  }

  return {
    table,
    search,
    status,
    loading,
    pagination,
    paginated,
    orderingDisabled,
    viewColumnOptions,
    viewTableColumns,
    viewSortOptions,
    viewSortField,
    viewSortDirection,
    onColumnsReorder,
    refresh,
    removeRows,
    reorder,
    from: computed(() =>
      paginated.value
        ? (pagination.value?.from ?? 0)
        : displayedRows.value.length
          ? 1
          : 0
    ),
    to: computed(() =>
      paginated.value ? (pagination.value?.to ?? 0) : displayedRows.value.length
    ),
    total: computed(() =>
      paginated.value
        ? (pagination.value?.total ?? 0)
        : displayedRows.value.length
    ),
  };
}
