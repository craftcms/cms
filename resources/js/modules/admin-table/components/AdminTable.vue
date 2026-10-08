<script setup lang="ts" generic="TData extends Record<string, any>">
  import type {Row, Table} from '@tanstack/vue-table';
  import type {CraftTableFeatures} from '@/modules/admin-table/craftTable';
  import type {BulkAction} from '@/modules/elements/types/actions';
  import AdminTableBulkActionsBar from './AdminTableBulkActionsBar.vue';
  import DataTable from '@/common/components/DataTable.vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
  import {usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {computed, type HTMLAttributes, ref} from 'vue';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';

  const props = withDefaults(
    defineProps<{
      table: Table<CraftTableFeatures, TData>;
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
      showFooter?: boolean;
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
      showFooter: true,
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

  const {
    onToggleAllSelected,
    selectRow,
    selectRowFromEvent,
    toggleRow,
    extendSelectionTo,
  } = useTableRowSelection(() => props.table, {
    selectable: () => props.selectable,
    readOnly,
  });

  // Captures modifier state from the native click, because craft-checkbox's
  // `model-value-changed` event does not carry `shiftKey`.
  const pendingShiftKey = ref(false);

  const leadingColumnTracks = computed(() =>
    props.selectable ? ['44px'] : []
  );

  function rowLabel(row: Row<CraftTableFeatures, TData>): string {
    return row.original.label ?? row.original.name ?? String(row.original.id);
  }

  function rowAttributes(row: Row<CraftTableFeatures, TData>): HTMLAttributes {
    if (!props.selectable) {
      return {};
    }

    return {
      tabindex: 0,
      class: {sel: row.getIsSelected()},
    };
  }

  function onRowClick(row: Row<CraftTableFeatures, TData>, event: MouseEvent) {
    if (props.loading) {
      return;
    }

    selectRowFromEvent(row, event);
  }

  function onRowKeydown(
    row: Row<CraftTableFeatures, TData>,
    index: number,
    event: KeyboardEvent
  ) {
    if (!props.selectable || props.loading) {
      return;
    }

    if (event.target !== event.currentTarget) {
      return;
    }

    const rows = props.table.getRowModel().rows;

    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
        toggleRow(row);
        break;
      case 'ArrowDown':
      case 'ArrowUp': {
        const target = rows[event.key === 'ArrowDown' ? index + 1 : index - 1];
        if (event.shiftKey && target) {
          extendSelectionTo(target);
        }
        break;
      }
    }
  }

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

  const footerVisible = computed(
    () =>
      props.showFooter &&
      (showBulkActions.value ||
        props.enableAdjustPageSize ||
        (props.total ?? 0) > 0 ||
        props.table.getPageCount() > 1)
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
        :with-bottom-border="!footerVisible"
        :leading-column-tracks="leadingColumnTracks"
        :row-attributes="rowAttributes"
        @row-click="onRowClick"
        @row-keydown="onRowKeydown"
        @reorder="(start, end) => emit('reorder', start, end)"
      >
        <template #leading-header>
          <th
            v-if="selectable"
            class="cp-table-cell cp-table-cell--header cp-table-cell--select"
            scope="col"
          >
            <craft-checkbox
              label-sr-only
              .checked="table.getIsAllRowsSelected()"
              .indeterminate="
                table.getIsSomeRowsSelected() && !table.getIsAllRowsSelected()
              "
              .disabled="readOnly || loading"
              @model-value-changed="
                onToggleAllSelected(($event.target as HTMLInputElement).checked)
              "
            >
              <label slot="label">{{ t('Select all') }}</label>
            </craft-checkbox>
          </th>
        </template>
        <template #leading-cells="{row, hideBottomBorder}">
          <td
            v-if="selectable"
            :class="{
              'cp-table-cell': true,
              'cp-table-cell--select': true,
              'border-b-0': hideBottomBorder,
            }"
          >
            <craft-checkbox
              label-sr-only
              .checked="row.getIsSelected()"
              .disabled="readOnly || loading || !row.getCanSelect()"
              @click="pendingShiftKey = $event.shiftKey"
              @model-value-changed="
                selectRow(row, {
                  checked: ($event.target as HTMLInputElement).checked,
                  shiftKey: pendingShiftKey,
                })
              "
            >
              <label slot="label">{{
                t('Select {label}', {label: rowLabel(row)})
              }}</label>
            </craft-checkbox>
          </td>
        </template>
        <template #empty-row v-if="$slots['empty-row']"
          ><slot name="empty-row"
        /></template>
      </DataTable>
    </div>
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
