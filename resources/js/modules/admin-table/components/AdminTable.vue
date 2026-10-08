<script setup lang="ts" generic="TData extends Record<string, any>">
  import type {Table} from '@tanstack/vue-table';
  import type {CraftTableFeatures} from '@/common/table/craftTable';
  import DataTable from '@/common/components/DataTable.vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';

  const props = withDefaults(
    defineProps<{
      table: Table<CraftTableFeatures, TData>;
      title?: string;
      reorderable?: boolean;
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
  }>();
  const showFooter = computed(
    () =>
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
        @reorder="(start, end) => emit('reorder', start, end)"
      >
        <template #empty-row v-if="$slots['empty-row']"
          ><slot name="empty-row"
        /></template>
      </DataTable>
    </div>
    <div class="admin-table__footer" v-if="showFooter">
      <PaginationControls
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
  </div>
</template>

<style scoped lang="scss">
  .admin-table {
    min-width: 0;
  }
  .admin-table--padded {
    .admin-table__header,
    .admin-table__body,
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
