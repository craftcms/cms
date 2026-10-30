<script setup lang="ts" generic="TData extends Record<string, any>">
  import type {ColumnDef, Table} from '@tanstack/vue-table';
  import type {CraftTableFeatures} from '@/common/table/craftTable';
  import type {BulkAction} from '@/modules/elements/types/actions';
  import AdminTableBulkActionsBar from './AdminTableBulkActionsBar.vue';
  import DataTable from '@/common/components/DataTable.vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
  import {usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {
    computed,
    defineComponent,
    h,
    ref,
    useSlots,
    useTemplateRef,
  } from 'vue';
  import {useMutationObserver, useResizeObserver} from '@vueuse/core';
  import {
    TableAppearance,
    TableSpacing,
    type TableAppearanceValue,
    type TableSpacingValue,
    type PaginationData,
  } from '@/common/types';

  import {useAdminTable} from '../useAdminTable';
  import type {
    AdminTableDataOptions,
    AdminTableReorder,
    AdminTableStatusOption,
  } from '../types';
  import AdminTableControls from './AdminTableControls.vue';
  import MoveToPageButton from './MoveToPageButton.vue';
  import CreateActionButton from './CreateActionButton.vue';

  const props = withDefaults(
    defineProps<{
      table?: Table<CraftTableFeatures, TData>;
      rows?: TData[];
      columns?: ColumnDef<CraftTableFeatures, TData, any>[];
      loadRows?: AdminTableDataOptions<TData>['loadRows'];
      storageKey?: string;
      pagination?: PaginationData | null;
      requestState?: AdminTableDataOptions<TData>['requestState'];
      pageSize?: number;
      searchable?: boolean;
      searchPlaceholder?: string | null;
      statusFilterOptions?: AdminTableStatusOption[];
      columnsToggleable?: boolean;
      hiddenColumnsByDefault?: string[];
      getSearchText?: AdminTableDataOptions<TData>['getSearchText'];
      getRowStatus?: AdminTableDataOptions<TData>['getRowStatus'];
      getSortValue?: AdminTableDataOptions<TData>['getSortValue'];
      canSelectRow?: AdminTableDataOptions<TData>['canSelectRow'];
      getRowLabel?: (row: TData) => string;
      reorderRows?: (move: AdminTableReorder<TData>) => Promise<void>;
      moveToPage?: (
        id: string | number,
        page: number,
        pageSize: number
      ) => Promise<void>;
      emptyMessage?: string | null;
      createLabel?: string | null;
      createUrl?: string | null;
      createMenuItems?: Array<{label: string; url: string}> | null;
      title?: string;
      reorderable?: boolean;
      selectable?: boolean;
      actions?: Array<BulkAction> | null;
      statuses?: Array<BulkAction> | null;
      idsField?: string;
      elementType?: string;
      source?: string | null;
      context?: string;
      readOnly?: boolean;
      loading?: boolean;
      interactionsDisabled?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      from?: number | null;
      to?: number | null;
      total?: number;
      showFooter?: boolean;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: number[];
      /**
       * How the table meets its container:
       *
       * - `plain` — sits inside the container’s padding (the default)
       * - `flush` — insets the header and footer by the page container’s
       *   padding, and lets the table span it, padding each row’s outer cells
       *   to match
       * - `raised` — sits in a raised pane
       */
      appearance?: TableAppearanceValue;
    }>(),
    {
      rows: () => [],
      columns: () => [],
      pageSize: 100,
      searchable: false,
      statusFilterOptions: () => [],
      columnsToggleable: false,
      hiddenColumnsByDefault: () => [],
      reorderable: false,
      selectable: false,
      actions: () => [],
      source: null,
      context: 'index',
      readOnly: undefined,
      loading: false,
      layout: 'auto',
      spacing: TableSpacing.Spacious,
      showFooter: true,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      appearance: TableAppearance.Plain,
    }
  );
  const tableBody = useTemplateRef<HTMLElement>('table-body');
  const scrollable = ref(false);

  function updateScrollable(): void {
    const body = tableBody.value;
    scrollable.value = !!body && body.scrollWidth > body.clientWidth;
  }

  useResizeObserver(tableBody, updateScrollable);
  useMutationObserver(tableBody, updateScrollable, {
    childList: true,
    subtree: true,
    characterData: true,
  });

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(
    () => props.readOnly ?? page.props.readOnly ?? false
  );
  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    'action-performed': [];
    'load-error': [error: unknown];
    'action-error': [error: unknown];
  }>();

  const managed = props.table
    ? null
    : useAdminTable<TData>(props, (error) => emit('load-error', error));
  const table = computed(() => props.table ?? managed!.table);
  const hasRows = computed(() => table.value.getRowModel().rows.length > 0);
  const loading = computed(
    () => props.loading || !!managed?.loading.value || moving.value
  );
  const slots = useSlots();
  // Shown below the table rather than in its body, so it stays in view when
  // the table scrolls sideways; the table's own empty row is left out then.
  const showEmptyState = computed(
    () => !!managed && !hasRows.value && !loading.value && !slots['empty-row']
  );
  const reorderable = computed(
    () =>
      props.reorderable && !managed?.orderingDisabled.value && !loading.value
  );
  const from = computed(() => props.from ?? managed?.from.value);
  const to = computed(() => props.to ?? managed?.to.value);
  const total = computed(() => props.total ?? managed?.total.value);
  const enableAdjustPageSize = computed(
    () => props.enableAdjustPageSize || !!managed?.paginated.value
  );
  const hasControls = computed(
    () =>
      managed &&
      (props.searchable ||
        props.statusFilterOptions.length ||
        props.columnsToggleable ||
        props.createUrl ||
        props.createMenuItems?.length)
  );
  const emptyLabel = computed(() =>
    managed?.search.value
      ? t('No results for “{search}”.', {search: managed.search.value})
      : managed?.status.value
        ? t('No results.')
        : (props.emptyMessage ?? t('Nothing to show.'))
  );
  const moving = ref(false);

  function clearSelection(): void {
    table.value.resetRowSelection();
  }
  function refresh(options?: {quiet?: boolean}): Promise<boolean> {
    return managed?.refresh(undefined, options) ?? Promise.resolve(true);
  }
  function removeRows(ids: Array<string | number>): void {
    managed?.removeRows(ids);
  }

  function onReorder(start: number, end: number): void {
    if (managed && props.reorderRows) {
      void managed
        .reorder(start, end, props.reorderRows)
        .catch((error) => emit('action-error', error));
    } else {
      emit('reorder', start, end);
    }
  }

  async function moveSelectedToPage(page: number): Promise<void> {
    const id = selectedIds.value[0];
    if (!managed || !props.moveToPage || id === undefined || moving.value)
      return;
    moving.value = true;
    try {
      await props.moveToPage(
        id,
        page,
        table.value.atoms.pagination.get().pageSize
      );
      clearSelection();
      await refresh();
    } catch (error) {
      emit('action-error', error);
    } finally {
      moving.value = false;
    }
  }
  const MoveToPageDisplay = defineComponent({
    setup() {
      return () =>
        h(MoveToPageButton, {
          currentPage: managed?.pagination.value?.current_page ?? 1,
          lastPage: managed?.pagination.value?.last_page ?? 1,
          loading: loading.value,
          onMove: moveSelectedToPage,
        });
    },
  });
  const actions = computed<BulkAction[]>(() => [
    ...(props.actions ?? []),
    ...(props.moveToPage &&
    managed?.paginated.value &&
    !managed.orderingDisabled.value &&
    table.value.getPageCount() > 1 &&
    selectedIds.value.length === 1
      ? [{type: 'display' as const, is: MoveToPageDisplay}]
      : []),
  ]);

  const selection = useTableRowSelection(() => table.value, {
    selectable: () => props.selectable,
    readOnly,
  });

  const selectedIds = computed(() =>
    table.value.getSelectedRowModel().rows.map((row) => row.original.id)
  );
  defineExpose({selectedIds, refresh, removeRows, clearSelection});

  const showBulkActions = computed(
    () =>
      props.selectable &&
      !readOnly.value &&
      selectedIds.value.length > 0 &&
      (actions.value.length > 0 || (props.statuses?.length ?? 0) > 0)
  );
  const actionContext = computed(() => ({
    elementType: props.elementType,
    source: props.source,
    context: props.context,
  }));

  function onActionPerformed() {
    table.value.resetRowSelection();
    void refresh();
    emit('action-performed');
  }

  const footerVisible = computed(
    () =>
      props.showFooter &&
      (showBulkActions.value ||
        enableAdjustPageSize.value ||
        (total.value ?? 0) > 0 ||
        table.value.getPageCount() > 1)
  );
</script>

<template>
  <component
    :is="appearance === TableAppearance.Raised ? 'craft-pane' : 'div'"
    v-bind="
      appearance === TableAppearance.Raised
        ? {padding: '0', appearance: 'raised'}
        : {}
    "
    :class="['admin-table', `admin-table--${appearance}`]"
  >
    <div
      v-if="$slots['table-header'] || hasControls"
      class="admin-table__header"
    >
      <slot name="table-header">
        <AdminTableControls
          v-if="managed && hasControls"
          v-model:search="managed.search.value"
          v-model:status="managed.status.value"
          v-model:sort-field="managed.viewSortField.value"
          v-model:sort-direction="managed.viewSortDirection.value"
          v-model:table-columns="managed.viewTableColumns.value"
          :searchable="searchable"
          :search-placeholder="searchPlaceholder"
          :status-filter-options="statusFilterOptions"
          :columns-toggleable="columnsToggleable"
          :view-column-options="managed.viewColumnOptions.value"
          :view-sort-options="managed.viewSortOptions.value"
          :create-label="createLabel"
          :create-url="createUrl"
          :create-menu-items="createMenuItems"
          @reorder-columns="managed.onColumnsReorder"
        />
      </slot>
    </div>
    <div
      ref="table-body"
      class="admin-table__body"
      :tabindex="scrollable ? 0 : undefined"
    >
      <DataTable
        :table="table"
        :title="title"
        :reorderable="reorderable"
        :read-only="readOnly"
        :loading="loading"
        :layout="layout"
        :spacing="spacing"
        :appearance="
          appearance === TableAppearance.Flush
            ? TableAppearance.Flush
            : TableAppearance.Plain
        "
        :with-bottom-border="!footerVisible"
        :empty-row="!showEmptyState"
        :selection="selection"
        :get-row-label="getRowLabel"
        :interactions-disabled="interactionsDisabled || loading"
        @reorder="onReorder"
      >
        <template #empty-row v-if="$slots['empty-row'] || managed">
          <slot name="empty-row"><span hidden /></slot>
        </template>
      </DataTable>
    </div>
    <craft-empty v-if="showEmptyState" :label="emptyLabel">
      <slot name="empty-actions">
        <CreateActionButton
          v-if="!readOnly"
          :label="createLabel ?? null"
          :url="createUrl"
          :menu-items="createMenuItems"
        />
      </slot>
    </craft-empty>
    <div class="admin-table__footer" v-if="footerVisible">
      <AdminTableBulkActionsBar
        v-if="showBulkActions"
        :selected-ids="selectedIds"
        :actions="actions"
        :statuses="statuses"
        :ids-field="idsField"
        :element-type="elementType"
        :action-context="actionContext"
        @performed="onActionPerformed"
        @clear="table.resetRowSelection()"
      />
      <PaginationControls
        v-else
        :disabled="loading || interactionsDisabled"
        :page-index="table.atoms.pagination.get().pageIndex"
        :page-size="table.atoms.pagination.get().pageSize"
        :page-count="table.getPageCount()"
        :paginated="
          Boolean(
            table.options.manualPagination ||
            'paginatedRowModel' in table.options.features
          )
        "
        :from="from"
        :to="to"
        :total="total"
        :enable-adjust-page-size="enableAdjustPageSize"
        :page-size-options="pageSizeOptions"
        @page-change="table.setPageIndex"
        @page-size-change="table.setPageSize"
      />
    </div>
  </component>
</template>

<style scoped lang="scss">
  .admin-table {
    min-width: 0;
  }
  .admin-table--flush {
    .admin-table__header,
    .admin-table__footer {
      padding-inline: var(--cp-container-padding);
    }
  }
  .admin-table__header {
    margin-block-end: var(--c-spacing-md);
  }
  .admin-table__body {
    overflow-x: auto;
  }
  .admin-table__footer {
    position: sticky;
    inset-block-end: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    min-height: var(--cp-footer-height);
    background-color: var(--c-surface-default);
  }
</style>
