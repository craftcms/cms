<script setup lang="ts">
  import type {Table} from '@tanstack/vue-table';
  import type {BulkAction} from '@/modules/elements/types/actions';
  import AdminTableBulkActionsBar from './AdminTableBulkActionsBar.vue';
  import DataTable from '@/common/components/DataTable.vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';

  const props = withDefaults(
    defineProps<{
      table: Table<any>;
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
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: number[];
    }>(),
    {
      reorderable: false,
      selectable: false,
      actions: () => [],
      source: null,
      context: 'index',
      loading: false,
      layout: 'auto',
      spacing: TableSpacing.Spacious,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
    }
  );
  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(
    () => props.readOnly ?? page.props.readOnly ?? false
  );
  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    'action-performed': [];
  }>();
  const selectedIds = computed(() =>
    props.table.getSelectedRowModel().rows.map((row) => row.original.id)
  );
  const showBulkActions = computed(
    () =>
      props.selectable &&
      !readOnly.value &&
      selectedIds.value.length > 0 &&
      ((props.actions?.length ?? 0) > 0 || (props.statuses?.length ?? 0) > 0)
  );
  const actionContext = computed(() => ({
    elementType: props.elementType,
    source: props.source,
    context: props.context,
  }));

  function onActionPerformed() {
    props.table.resetRowSelection();
    emit('action-performed');
  }

  const showFooter = computed(
    () =>
      showBulkActions.value ||
      props.enableAdjustPageSize ||
      (props.total ?? 0) > 0 ||
      props.table.getPageCount() > 1
  );
</script>

<template>
  <div class="admin-table">
    <div v-if="$slots['table-header']" class="admin-table__header">
      <slot name="table-header" />
    </div>
    <div class="admin-table__body">
      <DataTable
        :table="table"
        :title="title"
        :reorderable="reorderable"
        :read-only="readOnly"
        :loading="loading"
        :layout="layout"
        :spacing="spacing"
        :with-bottom-border="!showFooter"
        @reorder="(start, end) => emit('reorder', start, end)"
      >
        <template #empty-row v-if="$slots['empty-row']"
          ><slot name="empty-row"
        /></template>
      </DataTable>
    </div>
    <div class="admin-table__footer" v-if="showFooter">
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
        :page-index="table.getState().pagination.pageIndex"
        :page-size="table.getState().pagination.pageSize"
        :page-count="table.getPageCount()"
        :paginated="
          Boolean(
            table.options.manualPagination ||
            table.options.getPaginationRowModel
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
  </div>
</template>

<style scoped lang="scss">
  .admin-table {
    min-width: 0;
  }
  .admin-table__header,
  .admin-table__body,
  .admin-table__footer {
    padding-inline: var(--cp-container-padding);
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
