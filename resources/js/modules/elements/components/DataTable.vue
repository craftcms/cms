<script setup lang="ts">
  import {type Column, FlexRender, type Table} from '@tanstack/vue-table';
  import {t} from '@craftcms/ui';
  import type CraftSpinner from '@craftcms/ui/components/spinner/spinner';
  import {
    computed,
    type HTMLAttributes,
    nextTick,
    ref,
    useId,
    useTemplateRef,
    watch,
  } from 'vue';
  import {useReorderableRows} from '@/modules/admin-table/composables/useReorderableRows';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';
  import ColumnHeaderTitle from '@/modules/admin-table/components/ColumnHeaderTitle.vue';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import Empty from '@/common/components/Empty.vue';
  import {usePage} from '@inertiajs/vue3';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';

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
    }>(),

    {
      reorderable: false,
      selectable: false,
      loading: false,
      layout: 'auto',
    }
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
  }>();

  const page = usePage<{readOnly: boolean}>();
  const loadingRef = useTemplateRef<CraftSpinner>('loading-ref');
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {
    onToggleAllSelected,
    selectRow,
    selectRowFromEvent,
    toggleRow,
    extendSelectionTo,
  } = useElementIndexSelection(() => props.table, {
    selectable: () => props.selectable ?? false,
    readOnly,
    actions: () => [], // actions/bulk bar live on BaseElementIndex
  });

  // Captures modifier state from the native click, because craft-checkbox's
  // `model-value-changed` event does not carry `shiftKey`.
  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }

  const {navigateToFolder, isFolderRow, rowMoveAttrs} = useFolderNavigation();

  // Folder rows (asset index) navigate into the folder on click, except when
  // the click lands on an interactive control (checkbox, a real link, …).
  // Other rows fall through to the normal click-to-select behavior.
  function onRowClick(row: any, event: MouseEvent) {
    if (!isFolderRow(row.original)) {
      selectRowFromEvent(row, event);
      return;
    }

    if (
      event.target instanceof HTMLElement &&
      event.target.closest(
        'a[href], button, input, craft-checkbox, craft-reorder-button'
      )
    ) {
      return;
    }
    navigateToFolder(row.original.folderUrl);
  }

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

  function resolveMetaClasses(value: HTMLAttributes['class']) {
    return value;
  }

  // Re-sorting reloads the index's data, and `loading` swaps the whole
  // <table> out for the spinner while that happens (see `v-if="loading"`
  // below), tearing down the sort button the user just pressed along with
  // it. Move focus onto the spinner once it mounts — `craft-spinner` has
  // its own internal tabindex="-1" wrapper and forwards `.focus()` to it —
  // then return focus to the same column's sort button once the table
  // remounts with the new data.
  const pendingSortFocusHeaderId = ref<string | null>(null);

  // `craft-spinner`'s default slot is its accessible name (a visually-hidden
  // span) — without it, a screen reader announces nothing when focus lands
  // there. Distinguish the sort-triggered reload from any other cause
  // (filters, pagination, source switches, …) since only the former moves
  // focus onto the spinner in the first place.
  const loadingLabel = computed(() =>
    pendingSortFocusHeaderId.value ? t('Sorting') : t('Loading')
  );

  function onSortColumn(
    column: Column<any>,
    headerId: string,
    event: MouseEvent
  ) {
    pendingSortFocusHeaderId.value = headerId;
    column.getToggleSortingHandler()?.(event);
  }

  watch(
    () => props.loading,
    async (isLoading, wasLoading) => {
      if (!pendingSortFocusHeaderId.value) return;

      if (isLoading) {
        await nextTick();
        loadingRef.value?.focus();
        return;
      }

      if (wasLoading) {
        const headerId = pendingSortFocusHeaderId.value;
        pendingSortFocusHeaderId.value = null;
        await nextTick();
        document
          .getElementById(`header-${headerId}`)
          ?.querySelector<HTMLButtonElement>('button')
          ?.focus();
      }
    }
  );

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

    return {
      '--table-column-count': columnCount,
      '--table-template-columns': gridDef.join(' '),
    };
  });

  function getRowPosition(index: number) {
    if (index === 0) {
      return 'first';
    }

    if (index === props.table.getRowModel().rows.length - 1) {
      return 'last';
    }

    return 'middle';
  }

  function focusRowByIndex(index: number, el: HTMLElement) {
    const table = el.closest('table');
    const rows = table?.querySelectorAll<HTMLElement>('tbody > tr[tabindex]');
    rows?.[index]?.focus();
  }

  function onRowKeydown(
    row: any,
    index: number | string,
    event: KeyboardEvent
  ) {
    if (!props.selectable) return;
    const rows = props.table.getRowModel().rows;
    if (!(event.currentTarget instanceof HTMLElement)) return;
    const target = event.currentTarget;
    index = Number(index);
    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
        if (isFolderRow(row.original)) {
          navigateToFolder(row.original.folderUrl);
          break;
        }
        toggleRow(row);
        break;
      case 'ArrowDown': {
        event.preventDefault();
        const next = Math.min(index + 1, rows.length - 1);
        const nextRow = rows[next];
        if (event.shiftKey && nextRow) extendSelectionTo(nextRow);
        focusRowByIndex(next, target);
        break;
      }
      case 'ArrowUp': {
        event.preventDefault();
        const prev = Math.max(index - 1, 0);
        const prevRow = rows[prev];
        if (event.shiftKey && prevRow) extendSelectionTo(prevRow);
        focusRowByIndex(prev, target);
        break;
      }
    }
  }

  function checkboxValue(event: Event): boolean {
    return event.target instanceof HTMLInputElement && event.target.checked;
  }
</script>

<template>
  <div v-if="loading" class="grid place-items-center min-h-20">
    <craft-spinner ref="loading-ref">{{ loadingLabel }}</craft-spinner>
  </div>
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
      <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
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
            @model-value-changed="
              onToggleAllSelected(($event.target as HTMLInputElement).checked)
            "
          >
            <label slot="label">{{ t('Select all') }}</label>
          </craft-checkbox>
        </th>
        <th
          v-for="header in headerGroup.headers"
          :key="header.id"
          :colSpan="header.colSpan"
          :id="`header-${header.id}`"
          :class="[
            {
              'cp-table-cell': true,
              'cp-table-cell--header': true,
              'cursor-pointer select-none': header.column.getCanSort(),
            },
            resolveMetaClasses(header.column.columnDef.meta?.columnClass),
            resolveMetaClasses(header.column.columnDef.meta?.headerClass),
          ]"
          scope="col"
          :aria-sort="getAriaSortAttribute(header.column)"
        >
          <div
            class="flex gap-1 items-center"
            :class="{'sr-only': header.column.columnDef.meta?.headerSrOnly}"
          >
            <ColumnHeaderTitle
              :is-sortable="header.column.getCanSort()"
              :sort-instructions-id="columnSortInstructionId"
              @sort-column="onSortColumn(header.column, header.id, $event)"
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
          v-for="(row, rowIdx) in table.getRowModel().rows"
          :key="row.id"
          :ref="(el) => setRowRef(el as HTMLTableRowElement, row.id)"
          :tabindex="selectable ? 0 : undefined"
          v-bind="rowMoveAttrs(row.original)"
          :class="{
            row: true,
            'cp-table-row': true,
            'cp-table-row--folder': isFolderRow(row.original),
            'row--dragging':
              !readOnly && getDragState(row.id).type === 'is-dragging',
          }"
          @click="onRowClick(row, $event)"
          @keydown="onRowKeydown(row, rowIdx, $event)"
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
              @click="rememberShift($event)"
              @model-value-changed="
                selectRow(row, {
                  checked: ($event.target as HTMLInputElement).checked,
                  shiftKey: pendingShiftKey,
                })
              "
            >
              <label slot="label">{{ t('Select row') }}</label>
            </craft-checkbox>
          </td>
          <component
            v-for="cell in row.getVisibleCells()"
            :is="cell.column.columnDef.meta?.cellTag ?? 'td'"
            :key="cell.id"
            :class="[
              {
                'cp-table-cell': true,
                [`cp-table-cell--${cell.column.id}`]: true,
                'cp-table-cell--wrap': cell.column.columnDef.meta?.wrap,
              },
              resolveMetaClasses(cell.column.columnDef.meta?.columnClass),
              resolveMetaClasses(cell.column.columnDef.meta?.cellClass),
            ]"
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
</template>

<style scoped lang="scss">
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

  :deep(.cp-table-cell) {
    width: min-content;
  }

  // Selection column hugs its checkbox rather than claiming a data-column share.
  :deep(.cp-table-cell--select) {
    width: 1px;
    max-width: calc(30rem / 16);
    white-space: nowrap;
  }

  :deep(.cp-table-cell--title) {
    width: 45%;
    min-width: 14rem;
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

  .cp-table-row--folder {
    cursor: pointer;
  }
</style>
