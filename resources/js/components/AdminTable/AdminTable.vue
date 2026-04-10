<script setup lang="ts">
  import {type Column, FlexRender, type Column} from '@tanstack/vue-table';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, useId} from 'vue';
  import {useReorderableRows} from '@/composables/useReorderableRows';
  import {TableSpacing, type TableSpacingValue} from '@/types';
  import ColumnHeaderTitle from '@/components/AdminTable/ColumnHeaderTitle.vue';
  import ReorderButton from '@/components/ReorderButton.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import Select from '@/components/form/Select.vue';
  import Text from '@/components/Text.vue';
  import Empty from '@/components/Empty.vue';

  const props = withDefaults(
    defineProps<{
      table: any;
      title?: string;
      reorderable?: boolean;
      selectable?: boolean;
      readOnly?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
    }>(),

    {
      reorderable: false,
      selectable: true,
      layout: 'auto',
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
    }
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
  }>();

  const {setRowRef, getDragState, getDropState} = useReorderableRows({
    getRowIds: () => props.table.getRowModel().rows.map((row: any) => row.id),
    onReorder: (startIndex, finishIndex) => {
      emit('reorder', startIndex, finishIndex);
    },
    enabled: () => !props.readOnly && props.reorderable,
  });

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
  const showFooter = computed(
    () => showPagination.value || showPageSize.value || showDisplayedRows.value
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

  function getColumnSize(column: any) {
    if (column.columnDef.meta?.columnSize) {
      return column.columnDef.meta.columnSize;
    }
  }

  const tableStyles = computed(() => {
    const styles: {[key: string]: number} = {
      '--table-column-count': props.table.getAllColumns().length,
    };

    const columns = props.table.getAllColumns();
    const gridDef = columns.reduce(
      (acc: Array<string>, column: Column<any>) => {
        acc.push(column.columnDef.meta?.trackSize ?? `1fr`);
        return acc;
      },
      []
    );

    styles['--table-template-columns'] = gridDef.join(' ');

    return styles;
  });
</script>

<template>
  <div class="cp-table-wrapper">
    <div class="cp-table-header" v-if="$slots['search-form']">
      <slot name="search-form"></slot>
    </div>

    <table
      :class="{
        'cp-table': true,
        'cp-table--compact': spacing === TableSpacing.Compact,
        'cp-table--relaxed': spacing === TableSpacing.Relaxed,
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
                :isSortable="header.column.getCanSort()"
                :sortInstructionsId="columnSortInstructionId"
                @sort-column="header.column.getToggleSortingHandler()?.($event)"
              >
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />

                <craft-icon
                  v-if="
                    header.column.getCanSort() && !header.column.getIsSorted()
                  "
                  name="arrow-up-arrow-down"
                ></craft-icon>
                <craft-icon
                  v-else-if="header.column.getIsSorted() === 'asc'"
                  name="arrow-down"
                ></craft-icon>
                <craft-icon
                  v-else-if="header.column.getIsSorted() === 'desc'"
                  name="arrow-up"
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
                !readOnly && getDragState(row.id).type === 'dragging',
            }"
          >
            <template v-if="reorderable && !readOnly">
              <td class="cell cell--drag-handle">
                <div class="flex justify-center">
                  <ReorderButton></ReorderButton>
                </div>

                <!-- Drop indicator spans entire row, positioned from this cell -->
                <DropIndicator :edge="getDropState(row.id).edge" />

                <Teleport
                  v-if="getDragState(row.id).type === 'dragging'"
                  :to="getDragState(row.id).container"
                >
                  <slot name="drag-preview" :row="row"></slot>
                </Teleport>
              </td>
            </template>
            <component
              v-for="cell in row.getVisibleCells()"
              :is="cell.column.columnDef.meta?.cellTag ?? 'td'"
              :key="cell.id"
              :class="{
                'cp-table-cell': true,
                'cp-table-cell--wrap': cell.column.columnDef.meta?.wrap,
                ...resolveMetaClasses(cell.column.columnDef.meta?.columnClass),
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
          <tr style="--table-template-columns: 1fr; --_cell-spacing-inline: 0; --_cell-spacing-block: 0;">
            <td>
              <slot name="empty-row">
                <Empty :label="t('No results')" icon="empty-set" />
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <div class="cp-table-footer" v-if="showFooter">
      <div>
        <Text
          v-if="showDisplayedRows"
          template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
          :params="{from, to, total}"
        />
      </div>
      <div class="flex gap-1">
        <template v-if="showPagination">
          <craft-button
            type="button"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
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
            >
            </craft-input>
            of
            {{ table.getPageCount() }}
          </div>
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            size="small"
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
        <template v-if="showPageSize">
          {{ t('Items per page:') }}
          <Select
            small
            :options="pageSizeOptions"
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
    overflow-x: auto;
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
