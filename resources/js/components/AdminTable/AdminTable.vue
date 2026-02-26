<script setup lang="ts">
  import {FlexRender} from '@tanstack/vue-table';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed} from 'vue';
  import {useReorderableRows} from '@/composables/useReorderableRows';
  import {TableSpacing} from '@/types';
  import ReorderButton from '@/components/ReorderButton.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import Select from '@/components/form/Select.vue';
  import Text from '@/components/Text.vue';

  const props = withDefaults(
    defineProps<{
      table: any;
      reorderable?: boolean;
      selectable?: boolean;
      readOnly?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: 'compact' | 'relaxed';
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
    }>(),

    {
      reorderable: true,
      selectable: true,
      layout: 'auto',
      spacing: 'compact',
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
        'cp-table--auto': layout === 'auto',
      }"
    >
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
            :style="{width: `${header.getSize()}px`}"
            :id="`header-${header.id}`"
            :class="{
              cell: true,
              'cell--header': true,
              'cursor-pointer select-none': header.column.getCanSort(),
            }"
            @click="header.column.getToggleSortingHandler()?.($event)"
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
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          :ref="(el) => setRowRef(el as HTMLTableRowElement, row.id)"
          :class="{
            row: true,
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
            :style="{width: `${cell.column.getSize()}px`}"
            :class="{
              cell: true,
              'cell--wrap': cell.column.columnDef.meta?.wrap,
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
              size="3"
              :label="t('Current page')"
              label-sr-only
              center
              small
            >
            </craft-input>
            of
            {{ table.getPageCount() }}
          </div>
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
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

  :deep(.cell--wrap) {
    white-space: normal;
  }

  :deep(.cell--drag-handle) {
    width: 40px;
    padding-inline: var(--c-spacing-sm);
    position: relative;
    overflow: visible;
  }

  :deep(.drag-handle) {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    padding: var(--c-spacing-xs);
    background: none;
    border: 1px solid transparent;
    border-radius: var(--c-border-radius-sm);
    color: var(--c-color-neutral-text-secondary);
    transition: background-color 0.15s ease;

    &:hover {
      background-color: var(--c-color-neutral-bg-hovered);
    }

    &:active {
      cursor: grabbing;
    }
  }

  :deep(.row--dragging) {
    opacity: 0.4;
  }
</style>
