<script setup lang="ts">
  import {type Column, FlexRender} from '@tanstack/vue-table';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, useId} from 'vue';
  import {useReorderableRows} from '@/modules/admin-table/composables/useReorderableRows';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';
  import ColumnHeaderTitle from '@/modules/admin-table/components/ColumnHeaderTitle.vue';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import Select from '@/common/form/Select.vue';
  import Text from '@/common/components/Text.vue';
  import Empty from '@/common/components/Empty.vue';
  import LoadingSkeleton from '@/common/components/LoadingSkeleton.vue';
  import BulkActionsBar from '@/modules/elements/components/BulkActionsBar.vue';
  import type {BulkActionItem} from '@/modules/elements/types/actions';
  import {usePage} from '@inertiajs/vue3';
  import {Appearance} from '@craftcms/cp';

  const props = withDefaults(
    defineProps<{
      table: any;
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
      // Bulk-action context: when `actions` are passed and rows are selected, a
      // sticky bulk-actions bar is rendered in the footer.
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
    /** Re-emitted after a bulk action succeeds, so the page can refresh. */
    'action-performed': [];
  }>();

  // Selection is read straight off the tanstack table instance; keyed by
  // element id (see the page's `getRowId`) so it survives sort/pagination.
  const selectedIds = computed<Array<string | number>>(() =>
    props.table.getSelectedRowModel().rows.map((row: any) => row.original.id)
  );
  const hasSelection = computed(() => selectedIds.value.length > 0);
  const hasBulkActions = computed(() => (props.actions?.length ?? 0) > 0);

  function clearSelection() {
    props.table.resetRowSelection();
  }

  function onActionPerformed() {
    // The list refresh is owned by the page (it knows the Inertia route +
    // which props to reload); clearing selection is safe to do locally too,
    // but the page also clears after the refresh resolves.
    clearSelection();
    emit('action-performed');
  }

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  // Roughly match the skeleton to the table currently shown (the previous rows
  // stay mounted during a refetch), clamped so a large page size doesn't render
  // an excessive placeholder.
  const skeletonCount = computed(() => {
    const size =
      props.table.getRowModel().rows.length ||
      props.table.getState().pagination.pageSize ||
      6;
    return Math.min(Math.max(size, 3), 12);
  });

  // Skeleton column count mirrors the visible data columns plus the selection
  // column, so the placeholder lines up with the real header.
  const skeletonColumns = computed(
    () =>
      props.table.getVisibleLeafColumns().length + (props.selectable ? 1 : 0)
  );
  const {setRowRef, setHandleRef, getDragState, getDropState} =
    useReorderableRows({
      getRowIds: () => props.table.getRowModel().rows.map((row: any) => row.id),
      onReorder: (startIndex, finishIndex) => {
        emit('reorder', startIndex, finishIndex);
      },
      enabled: () => !props.readOnly && props.reorderable,
    });

  function getClosestEdge(rowId: string) {
    const state = getDropState(rowId);
    return state.type === 'is-over' ? state.closestEdge : null;
  }

  const id = useId();
  const columnSortInstructionId = `column-sort-instructions-${id}`;
  const titleString = computed(() => {
    return props.title ? `${props.title}, ` : null;
  });

  const pageIndexProxy = computed({
    get() {
      return props.table.getState().pagination.pageIndex + 1;
    },
    set(newValue) {
      if (newValue) {
        props.table.setPageIndex(parseInt(newValue) - 1);
      }
    },
  });

  const pageSizeProxy = computed({
    get() {
      return props.table.getState().pagination.pageSize;
    },
    set(newValue) {
      if (newValue) {
        props.table.setPageSize(parseInt(newValue));
      }
    },
  });

  const showPagination = computed(() => props.table.getPageCount() > 1);
  const showPageSize = computed(() => props.enableAdjustPageSize);
  const showDisplayedRows = computed(
    () => props.from && props.to && props.total
  );
  // The footer also stays visible while a selection exists so the sticky
  // bulk-actions bar can ride along with it.
  const showBulkActions = computed(
    () => props.selectable && hasBulkActions.value
  );
  // While the bulk-actions bar owns the footer, the pagination + page-size
  // controls are hidden so the row reads as a single selection toolbar.
  const bulkActionsActive = computed(
    () => showBulkActions.value && hasSelection.value
  );
  const showFooter = computed(
    () =>
      showPagination.value ||
      showPageSize.value ||
      showDisplayedRows.value ||
      (showBulkActions.value && hasSelection.value)
  );

  function resolveMetaClasses(value: any) {
    if (!value) {
      return {};
    }

    if (typeof value === 'string') {
      return {[value]: true};
    }

    return value;
  }

  function getAriaSortAttribute(
    column: Column<any>
  ): 'ascending' | 'descending' | 'none' | undefined {
    if (column.getCanSort()) {
      if (column.getIsSorted()) {
        return column.getIsSorted() === 'asc' ? 'ascending' : 'descending';
      }
      return 'none';
    }
  }

  const visibleColumnCount = computed(() => {
    const columns = props.table.getAllColumns();
    const visibleColumns = columns.filter((column: Column<any>) =>
      column.getIsVisible()
    );
    let columnCount = visibleColumns.length;

    if (props.reorderable) {
      columnCount += 1;
    }

    if (props.selectable) {
      columnCount += 1;
    }

    return columnCount;
  });

  const tableStyles = computed(() => {
    const columns = props.table.getAllColumns();
    const visibleColumns = columns.filter((column: Column<any>) =>
      column.getIsVisible()
    );

    const columnCount = visibleColumnCount.value;

    const styles: {[key: string]: number} = {
      '--table-column-count': columnCount,
    };

    const gridDef = visibleColumns.reduce(
      (acc: Array<string>, column: Column<any>) => {
        acc.push(column.columnDef.meta?.trackSize ?? `minmax(0, 1fr)`);
        return acc;
      },
      []
    );

    // Leading utility columns, in render order: reorder handle, then select.
    if (props.selectable) {
      gridDef.unshift('44px');
    }

    if (props.reorderable) {
      gridDef.unshift('44px');
    }

    styles['--table-template-columns'] = gridDef.join(' ');

    return styles;
  });

  // craft-checkbox (Lion) fires `model-value-changed` on programmatic `.checked`
  // updates too, not just user clicks. Since we bind `.checked` to the selection
  // state, only act when the event value actually differs from current state —
  // that's true for genuine user interaction but not for our own state sync, so
  // it breaks the feedback loop (and avoids the partial-selection header, set to
  // `checked=false`, being misread as "deselect all"). Read-only fully inert.
  function onToggleAllSelected(event: Event) {
    if (readOnly.value) {
      return;
    }
    const checked = (event.target as HTMLInputElement).checked;
    if (checked !== props.table.getIsAllRowsSelected()) {
      props.table.toggleAllRowsSelected(checked);
    }
  }

  function onToggleRowSelected(row: any, event: Event) {
    if (readOnly.value) {
      return;
    }
    const checked = (event.target as HTMLInputElement).checked;
    if (checked !== row.getIsSelected()) {
      row.toggleSelected(checked);
    }
  }

  function getRowPosition(index: number) {
    if (index === 0) {
      return 'first';
    }

    if (index === props.table.getRowModel().rows.length - 1) {
      return 'last';
    }

    return 'middle';
  }
</script>

<template>
  <div class="cp-table-wrapper">
    <div class="cp-table-header" v-if="$slots['table-header']">
      <slot name="table-header"></slot>
    </div>

    <div class="cp-table-body" :aria-busy="loading ? 'true' : undefined">
      <LoadingSkeleton
        v-if="loading"
        variant="table"
        :count="skeletonCount"
        :columns="skeletonColumns"
      />
      <table
        v-else
        :class="{
          'cp-table': true,
          'cp-table--grid': false,
          'cp-table--compact': spacing === TableSpacing.Compact,
          'cp-table--spacious': spacing === TableSpacing.Spacious,
          'cp-table--auto': layout === 'auto',
        }"
        :style="tableStyles"
      >
        <caption class="sr-only">
          {{
            titleString
          }}
          <span :id="columnSortInstructionId">{{
            t('Column headers with buttons are sortable')
          }}</span>
        </caption>
        <thead>
          <tr
            v-for="headerGroup in table.getHeaderGroups()"
            :key="headerGroup.id"
          >
            <template v-if="!readOnly && reorderable">
              <th class="cell cell--header">
                <span class="sr-only">Reorder</span>
              </th>
            </template>
            <th
              v-if="selectable"
              class="cp-table-cell cp-table-cell--header cp-table-cell--select"
              scope="col"
            >
              <craft-checkbox
                label-sr-only
                .checked="table.getIsAllRowsSelected()"
                .indeterminate="table.getIsSomeRowsSelected()"
                .disabled="readOnly"
                @model-value-changed="onToggleAllSelected"
              >
                <label slot="label">{{ t('Select all') }}</label>
              </craft-checkbox>
            </th>
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              :colSpan="header.colSpan"
              :id="`header-${header.id}`"
              :class="{
                'cp-table-cell': true,
                'cp-table-cell--header': true,
                'cursor-pointer select-none': header.column.getCanSort(),
              }"
              scope="col"
              :aria-sort="getAriaSortAttribute(header.column)"
            >
              <div
                class="flex gap-1 items-center"
                :class="{
                  'sr-only': header.column.columnDef.meta?.headerSrOnly,
                  ...resolveMetaClasses(
                    header.column.columnDef.meta?.columnClass
                  ),
                  ...resolveMetaClasses(
                    header.column.columnDef.meta?.headerClass
                  ),
                }"
              >
                <ColumnHeaderTitle
                  :is-sortable="header.column.getCanSort()"
                  :sort-instructions-id="columnSortInstructionId"
                  @sort-column="
                    header.column.getToggleSortingHandler()?.($event)
                  "
                >
                  <FlexRender
                    v-if="!header.isPlaceholder"
                    :render="header.column.columnDef.header"
                    :props="header.getContext()"
                  />&nbsp;<craft-icon
                    v-if="
                      header.column.getCanSort() && !header.column.getIsSorted()
                    "
                    name="arrow-up-arrow-down"
                  ></craft-icon>
                  <craft-icon
                    v-else-if="header.column.getIsSorted() === 'asc'"
                    name="asc"
                  ></craft-icon>
                  <craft-icon
                    v-else-if="header.column.getIsSorted() === 'desc'"
                    name="desc"
                  ></craft-icon>
                </ColumnHeaderTitle>

                <template v-if="header.column.columnDef.meta?.headerTip">
                  <craft-info-icon>{{
                    header.column.columnDef.meta.headerTip
                  }}</craft-info-icon>
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="table.getRowModel().rows.length > 0">
            <tr
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :ref="(el) => setRowRef(el as HTMLTableRowElement, row.id)"
              :class="{
                row: true,
                'cp-table-row': true,
                'row--dragging':
                  !readOnly && getDragState(row.id).type === 'is-dragging',
              }"
            >
              <template v-if="reorderable && !readOnly">
                <td>
                  <div>
                    <craft-reorder-button
                      @reorder="
                        (e: CustomEvent<{direction: 'up' | 'down'}>) =>
                          emit(
                            'reorder',
                            row.index,
                            e.detail.direction === 'up'
                              ? row.index - 1
                              : row.index + 1
                          )
                      "
                      :position="getRowPosition(row.index)"
                      :ref="(el: any) => setHandleRef(el, row.id)"
                    ></craft-reorder-button>
                  </div>

                  <!-- Drop indicator spans entire row, positioned from this cell -->
                  <DropIndicator :edge="getClosestEdge(row.id)" />
                </td>
              </template>
              <td v-if="selectable" class="cp-table-cell cp-table-cell--select">
                <craft-checkbox
                  label-sr-only
                  .checked="row.getIsSelected()"
                  .disabled="readOnly || !row.getCanSelect()"
                  @model-value-changed="onToggleRowSelected(row, $event)"
                >
                  <label slot="label">{{ t('Select row') }}</label>
                </craft-checkbox>
              </td>
              <component
                v-for="cell in row.getVisibleCells()"
                :is="cell.column.columnDef.meta?.cellTag ?? 'td'"
                :key="cell.id"
                :class="{
                  'cp-table-cell': true,
                  'cp-table-cell--wrap': cell.column.columnDef.meta?.wrap,
                  ...resolveMetaClasses(
                    cell.column.columnDef.meta?.columnClass
                  ),
                  ...resolveMetaClasses(cell.column.columnDef.meta?.cellClass),
                }"
              >
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </component>
            </tr>
          </template>
          <template v-else>
            <tr
              style="
                --table-template-columns: 1fr;
                --_cell-spacing-inline: 0;
                --_cell-spacing-block: 0;
              "
            >
              <td :colspan="visibleColumnCount">
                <slot name="empty-row">
                  <Empty :label="t('No results')" icon="empty-set" />
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div
      class="cp-table-footer"
      :class="{
        'cp-table-footer--has-selection': showBulkActions && hasSelection,
      }"
      v-if="showFooter"
    >
      <div class="cp-table-footer__lead">
        <BulkActionsBar
          v-if="showBulkActions && hasSelection"
          :selected-ids="selectedIds"
          :actions="actions"
          :element-type="elementType ?? ''"
          :source="source"
          :context="context"
          @performed="onActionPerformed"
          @clear="clearSelection"
        />
        <Text
          v-else-if="showDisplayedRows"
          template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
          :params="{from: from ?? 0, to: to ?? 0, total: total ?? 0}"
        />
      </div>
      <div class="flex gap-1">
        <template v-if="showPagination && !bulkActionsActive">
          <craft-button
            type="button"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
            :appearance="Appearance.Plain"
            icon
            size="small"
          >
            <craft-icon
              name="chevron-left"
              :label="t('Previous page')"
            ></craft-icon>
          </craft-button>
          <div class="flex items-center gap-1 mx-2">
            Page
            <craft-input
              type="text"
              v-model="pageIndexProxy"
              maxlength="3"
              :label="t('Current page')"
              label-sr-only
              center
              size="small"
              style="width: 4ch"
            />
            of
            {{ table.getPageCount() }}
          </div>
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            size="small"
            :appearance="Appearance.Plain"
            icon
          >
            <craft-icon
              name="chevron-right"
              :label="t('Next page')"
            ></craft-icon>
          </craft-button>
        </template>
      </div>
      <div class="flex gap-2 items-center">
        <template v-if="showPageSize && !bulkActionsActive">
          {{ t('Items per page:') }}
          <Select
            small
            :options="pageSizeOptions!"
            v-model="pageSizeProxy"
            class="w-auto"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-table-wrapper {
    overflow-y: clip;
  }

  .cp-table-body {
    overflow-x: auto;
  }

  .cp-table-footer {
    position: sticky;
    bottom: 0;
    z-index: 1;
    background-color: var(--c-surface-default);
  }

  // When the bulk-actions bar takes over the lead zone, let it own the row's
  // width so the "N selected" + actions layout can breathe.
  .cp-table-footer--has-selection .cp-table-footer__lead {
    flex: 1 1 auto;
  }

  :deep(.cell) {
    white-space: nowrap;
  }

  :deep(.cell--header) {
    white-space: nowrap;
  }

  :deep(.cell--header[aria-sort]) {
    &:hover,
    &:focus-within {
      background-color: var(--c-color-neutral-fill-loud);
      color: var(--c-color-neutral-on-loud);
    }
  }

  :deep(.cell--wrap) {
    white-space: normal;
  }

  // Selection column hugs its checkbox rather than claiming a data-column share.
  :deep(.cp-table-cell--select) {
    width: 1px;
    white-space: nowrap;
  }

  :deep(.cell--drag-handle) {
    width: 40px;
    padding-inline: var(--c-spacing-sm);
    position: relative;
    overflow: visible;
  }

  :deep(.row--dragging) {
    opacity: 0.4;
  }
</style>
