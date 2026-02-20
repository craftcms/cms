<script setup lang="ts">
  import {FlexRender} from '@tanstack/vue-table';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue';
  import {useTableDragAndDrop} from '@/composables/useTableDragAndDrop';
  import ReorderButton from '@/components/ReorderButton.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import Select from '@/components/form/Select.vue';

  const props = withDefaults(
    defineProps<{
      table: any;
      reorderable?: boolean;
      selectable?: boolean;
      readOnly?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: 'compact' | 'relaxed';
    }>(),

    {reorderable: true, selectable: true, layout: 'auto', spacing: 'compact'}
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
  }>();

  // Row element refs for drag-and-drop registration
  const rowRefs = ref<Map<string, HTMLTableRowElement>>(new Map());
  const handleRefs = ref<Map<string, HTMLElement>>(new Map());
  const cleanupFns = ref<Map<string, () => void>>(new Map());

  const {registerRow, getDragState, getDropState} = useTableDragAndDrop({
    onReorder: (startIndex, finishIndex) => {
      emit('reorder', startIndex, finishIndex);
    },
    getRowId: (row) => row.id,
  });

  function setRowRef(el: HTMLTableRowElement | null, rowId: string) {
    if (el) {
      rowRefs.value.set(rowId, el);
    } else {
      rowRefs.value.delete(rowId);
    }
  }

  function registerRows() {
    if (props.readOnly) return;

    // Clean up existing registrations
    cleanupFns.value.forEach((fn) => fn());
    cleanupFns.value.clear();

    // Register each row
    props.table.getRowModel().rows.forEach((row: any, index: number) => {
      const rowEl = rowRefs.value.get(row.id);
      const handleEl = handleRefs.value.get(row.id);

      if (rowEl) {
        const cleanup = registerRow(rowEl, handleEl ?? null, row.id, index);
        cleanupFns.value.set(row.id, cleanup);
      }
    });
  }

  // Re-register rows when data changes
  watch(
    () => props.table.getRowModel().rows,
    () => {
      nextTick(registerRows);
    },
    {deep: true}
  );

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

  onMounted(() => {
    nextTick(registerRows);
  });

  onUnmounted(() => {
    cleanupFns.value.forEach((fn) => fn());
  });
</script>

<template>
  <div class="admin-table-wrapper">
    <div class="cp-table-header">
      <slot name="search-form"></slot>
    </div>
    <table
      :class="{
        'cp-table': true,
        'cp-table--compact': spacing === 'compact',
        'cp-table--relaxed': spacing === 'relaxed',
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
            :class="{
              cell: true,
              'cell--header': true,
              'cursor-pointer select-none': header.column.getCanSort(),
            }"
            @click="header.column.getToggleSortingHandler()?.($event)"
          >
            <div class="flex gap-1 items-center">
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
            'row--dragging': !readOnly && getDragState(row.id) === 'dragging',
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
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            :style="{width: `${cell.column.getSize()}px`}"
            :class="{
              cell: true,
              'cell--wrap': cell.column.columnDef.meta?.wrap,
            }"
          >
            <FlexRender
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <div class="cp-table-footer">
      <div class="flex gap-3 items-center justify-between">
        <div class="flex gap-1">
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
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            icon
          >
            <craft-icon
              name="chevron-right"
              label="t('Next page')"
            ></craft-icon>
          </craft-button>
        </div>
        <span class="flex items-center gap-1">
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
        </span>
      </div>
      <div class="flex gap-2 items-center">
        {{ t('Items per page:') }}
        <Select
          :options="['25', '50', '100']"
          v-model="pageSizeProxy"
          class="w-auto"
        />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .admin-table-wrapper {
    overflow-y: clip;
    overflow-x: auto;
  }

  .cell {
    white-space: nowrap;
  }

  .cell--wrap {
    white-space: normal;
  }

  .cell--drag-handle {
    width: 40px;
    padding-inline: var(--c-spacing-sm);
    position: relative;
    overflow: visible; // Allow drop indicator to extend beyond cell
  }

  // Drag handle styles
  .drag-handle {
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

  // Drag states
  .row--dragging {
    opacity: 0.4;
  }
</style>
