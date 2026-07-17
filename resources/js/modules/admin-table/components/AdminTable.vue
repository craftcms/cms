<script setup lang="ts">
  import {computed} from 'vue';
  import type {Table} from '@tanstack/vue-table';
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import {type TableSpacingValue} from '@/common/types';
  import type {BulkActionItem} from '@/modules/elements/types/actions';

  const props = withDefaults(
    defineProps<{
      table: Table<any>;
      title?: string;
      reorderable?: boolean;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
      actions?: Array<BulkActionItem> | null;
      elementType?: string;
      source?: string | null;
      context?: string;
    }>(),
    {
      reorderable: false,
      selectable: false,
      loading: false,
      layout: 'auto',
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      actions: () => [],
      source: null,
      context: 'index',
    }
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    'action-performed': [];
  }>();

  const baseProps = computed(() => ({
    table: props.table,
    selectable: props.selectable,
    readOnly: props.readOnly,
    loading: props.loading,
    from: props.from,
    to: props.to,
    total: props.total,
    enableAdjustPageSize: props.enableAdjustPageSize,
    pageSizeOptions: props.pageSizeOptions,
    actions: props.actions,
    elementType: props.elementType,
    source: props.source,
    context: props.context,
  }));

  const viewProps = computed(() => ({
    table: props.table,
    selectable: props.selectable,
    readOnly: props.readOnly,
    loading: props.loading,
    reorderable: props.reorderable,
    layout: props.layout,
    spacing: props.spacing,
    title: props.title,
  }));
</script>

<template>
  <BaseElementIndex
    v-bind="baseProps"
    @action-performed="emit('action-performed')"
  >
    <template #header v-if="$slots['table-header']">
      <slot name="table-header"></slot>
    </template>
    <template #body>
      <DataTable
        v-bind="viewProps"
        @reorder="(s: number, f: number) => emit('reorder', s, f)"
      >
        <template #empty-row v-if="$slots['empty-row']">
          <slot name="empty-row" />
        </template>
      </DataTable>
    </template>
  </BaseElementIndex>
</template>
