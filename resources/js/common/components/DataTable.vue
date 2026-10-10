<script setup lang="ts" generic="TData extends Record<string, any>">
  import {
    type Column,
    FlexRender,
    type Row,
    type Table,
  } from '@tanstack/vue-table';
  import {
    type CraftTableFeatures,
    tableHeaderId,
  } from '@/common/table/craftTable';
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
  import type {NestedReorderDirection} from '@craftcms/ui';
  import type {TableRowSelection} from '@/common/composables/useTableRowSelection';
  import {useReorderableRows} from '@/common/composables/useReorderableRows';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';
  import ColumnHeaderTitle from '@/common/components/ColumnHeaderTitle.vue';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  const props = withDefaults(
    defineProps<{
      table: Table<CraftTableFeatures, TData>;
      selection?: TableRowSelection<TData>;
      getRowLabel?: (row: TData) => string;
      rowBehavior?: {
        onClick?: (row: TData, event: MouseEvent) => boolean;
        onKeydown?: (row: TData, event: KeyboardEvent) => boolean;
      };
      title?: string;
      reorderable?: boolean;
      /**
       * Disables selection, sorting and reordering while a request runs,
       * keeping the controls in place so the layout doesn't shift.
       */
      interactionsDisabled?: boolean;
      readOnly?: boolean;
      leadingColumnTracks?: string[];
      rowAttributes?: (row: Row<CraftTableFeatures, TData>) => HTMLAttributes;
      loading?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      withBottomBorder?: boolean;
      /**
       * Pads the start and end of each row by `--cp-container-padding`, for a
       * table that spans its container's padding rather than sitting inside it.
       */
      flush?: boolean;
    }>(),

    {
      reorderable: false,
      interactionsDisabled: false,
      readOnly: false,
      leadingColumnTracks: () => [],
      loading: false,
      layout: 'auto',
      withBottomBorder: true,
      flush: false,
    }
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    rowClick: [row: Row<CraftTableFeatures, TData>, event: MouseEvent];
    rowKeydown: [
      row: Row<CraftTableFeatures, TData>,
      index: number,
      event: KeyboardEvent,
    ];
    rowRef: [element: Element | null, row: Row<CraftTableFeatures, TData>];
  }>();

  const selectable = computed(
    () => props.selection?.selection.enabled.value ?? false
  );
  const readOnly = computed(
    () => props.readOnly || !!props.selection?.readOnly.value
  );
  const selectionDisabled = computed(
    () => readOnly.value || props.interactionsDisabled || props.loading
  );
  const leadingColumnTracks = computed(() => [
    ...props.leadingColumnTracks,
    ...(selectable.value ? ['44px'] : []),
  ]);
  const pendingShiftKey = ref(false);

  function rowLabel(row: Row<CraftTableFeatures, TData>): string {
    return (
      props.getRowLabel?.(row.original) ??
      String(row.original.label ?? row.original.name ?? row.original.id)
    );
  }

  function toggleAllSelected(event: Event): void {
    if (selectionDisabled.value) return;
    props.selection?.onToggleAllSelected(
      (event.target as HTMLInputElement).checked
    );
  }

  function selectRow(row: Row<CraftTableFeatures, TData>, event: Event): void {
    if (selectionDisabled.value) return;
    const shiftKey = pendingShiftKey.value;
    pendingShiftKey.value = false;

    props.selection?.selectRow(row, {
      checked: (event.target as HTMLInputElement).checked,
      shiftKey,
    });
  }

  function onRowClick(
    row: Row<CraftTableFeatures, TData>,
    event: MouseEvent
  ): void {
    emit('rowClick', row, event);
    if (
      props.rowBehavior?.onClick?.(row.original, event) ||
      event.defaultPrevented ||
      selectionDisabled.value
    )
      return;
    props.selection?.selectRowFromEvent(row, event);
  }

  const rootRef = useTemplateRef<HTMLElement>('root-ref');
  const loadingRef = useTemplateRef<CraftSpinner>('loading-ref');

  const {setRowRef, setHandleRef, getDragState, getDropState} =
    useReorderableRows({
      getRowIds: () => props.table.getRowModel().rows.map((row: any) => row.id),
      onReorder: (startIndex, finishIndex) => {
        emit('reorder', startIndex, finishIndex);
      },
      enabled: () =>
        !readOnly.value && props.reorderable && !props.interactionsDisabled,
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

  function captureFocusedHeaderId(): string | null {
    const active = document.activeElement;
    if (!(active instanceof HTMLElement) || !rootRef.value?.contains(active)) {
      return null;
    }
    return active.closest<HTMLElement>('th[id]')?.id ?? null;
  }

  watch(
    () => props.loading,
    async (isLoading, wasLoading) => {
      if (isLoading) {
        pendingSortFocusHeaderId.value = captureFocusedHeaderId();
        if (!pendingSortFocusHeaderId.value) return;
        await nextTick();
        loadingRef.value?.focus();
        return;
      }

      if (wasLoading && pendingSortFocusHeaderId.value) {
        const headerId = pendingSortFocusHeaderId.value;
        pendingSortFocusHeaderId.value = null;
        await nextTick();
        rootRef.value
          ?.querySelector<HTMLElement>(`#${CSS.escape(headerId)}`)
          ?.querySelector<HTMLButtonElement>('button')
          ?.focus();
      }
    }
  );

  function getAriaSortAttribute(
    column: Column<CraftTableFeatures, TData>
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
    const visibleColumns = columns.filter(
      (column: Column<CraftTableFeatures, TData>) => column.getIsVisible()
    );
    let columnCount = visibleColumns.length;

    if (props.reorderable) {
      columnCount += 1;
    }

    return columnCount + leadingColumnTracks.value.length;
  });

  const tableStyles = computed(() => {
    const columns = props.table.getAllColumns();
    const visibleColumns = columns.filter(
      (column: Column<CraftTableFeatures, TData>) => column.getIsVisible()
    );

    const columnCount = visibleColumnCount.value;

    const gridDef = visibleColumns.reduce(
      (acc: Array<string>, column: Column<CraftTableFeatures, TData>) => {
        acc.push(column.columnDef.meta?.trackSize ?? `minmax(0, 1fr)`);
        return acc;
      },
      []
    );

    if (props.reorderable) {
      gridDef.unshift('44px');
    }

    return {
      '--table-column-count': columnCount,
      '--table-template-columns': [
        ...leadingColumnTracks.value,
        ...gridDef,
      ].join(' '),
    };
  });

  function hideBottomBorder(rowIdx: number) {
    return (
      !props.withBottomBorder &&
      rowIdx === props.table.getRowModel().rows.length - 1
    );
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

  function focusRowByIndex(index: number, el: HTMLElement) {
    const table = el.closest('table');
    const rows = table?.querySelectorAll<HTMLElement>('tbody > tr[tabindex]');
    rows?.[index]?.focus();
  }

  function onRowKeydown(
    row: Row<CraftTableFeatures, TData>,
    index: number,
    event: KeyboardEvent
  ) {
    emit('rowKeydown', row, index, event);

    if (event.defaultPrevented || !(event.currentTarget instanceof HTMLElement))
      return;
    if (
      !event.currentTarget.hasAttribute('tabindex') ||
      event.target !== event.currentTarget
    )
      return;

    if (props.rowBehavior?.onKeydown?.(row.original, event)) {
      event.preventDefault();
      return;
    }

    if (props.interactionsDisabled || props.loading) return;

    if (
      selectable.value &&
      !readOnly.value &&
      (event.key === ' ' || event.key === 'Enter')
    ) {
      event.preventDefault();
      props.selection?.toggleRow(row);
      return;
    }

    const rows = props.table.getRowModel().rows;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const next =
        event.key === 'ArrowDown'
          ? Math.min(index + 1, rows.length - 1)
          : Math.max(index - 1, 0);
      if (selectable.value && !readOnly.value && event.shiftKey && rows[next]) {
        props.selection?.extendSelectionTo(rows[next]);
      }
      focusRowByIndex(next, event.currentTarget);
    }
  }
</script>

<template>
  <div ref="root-ref">
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
        'cp-table--flush': flush,
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
          <slot name="leading-header" />
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
              .disabled="selectionDisabled"
              @model-value-changed="toggleAllSelected"
              ><label slot="label">{{ t('Select all') }}</label></craft-checkbox
            >
          </th>
          <template v-if="!readOnly && reorderable">
            <th class="cell cell--header">
              <span class="sr-only">Reorder</span>
            </th>
          </template>
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            :colSpan="header.colSpan"
            :id="tableHeaderId(table, header.id)"
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
              class="flex gap-sm items-center [.text-center>&]:justify-center"
              :class="{'sr-only': header.column.columnDef.meta?.headerSrOnly}"
            >
              <ColumnHeaderTitle
                :is-sortable="header.column.getCanSort()"
                :disabled="interactionsDisabled"
                :sort-instructions-id="columnSortInstructionId"
                @sort-column="header.column.getToggleSortingHandler()?.($event)"
              >
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :header="header"
                /><template v-if="header.column.getCanSort()">&nbsp;</template
                ><craft-icon
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
            :ref="
              (el) => {
                setRowRef(el as HTMLTableRowElement, row.id);
                emit('rowRef', el as Element | null, row);
              }
            "
            :class="{
              row: true,
              'cp-table-row': true,
              sel: !!selection && row.getIsSelected(),
              'row--dragging':
                !readOnly && getDragState(row.id).type === 'is-dragging',
            }"
            :tabindex="selectable ? 0 : undefined"
            v-bind="rowAttributes?.(row)"
            @click="onRowClick(row, $event)"
            @keydown="onRowKeydown(row, rowIdx, $event)"
          >
            <slot
              name="leading-cells"
              :row="row"
              :hide-bottom-border="hideBottomBorder(rowIdx)"
            />
            <td
              v-if="selectable"
              class="cp-table-cell cp-table-cell--select"
              :class="{'border-b-0': hideBottomBorder(rowIdx)}"
            >
              <craft-checkbox
                label-sr-only
                .checked="row.getIsSelected()"
                .disabled="selectionDisabled || !row.getCanSelect()"
                @click="pendingShiftKey = $event.shiftKey"
                @model-value-changed="selectRow(row, $event)"
                ><label slot="label">{{
                  t('Select {label}', {label: rowLabel(row)})
                }}</label></craft-checkbox
              >
            </td>
            <template v-if="reorderable && !readOnly">
              <td :class="{'border-b-0': hideBottomBorder(rowIdx)}">
                <div>
                  <craft-reorder-button
                    .disabled="interactionsDisabled"
                    @craft-reorder="
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
            <component
              v-for="(cell, cellIdx) in row.getVisibleCells()"
              :is="cell.column.columnDef.meta?.cellTag ?? 'td'"
              :key="cell.id"
              :class="[
                {
                  'cp-table-cell': true,
                  [`cp-table-cell--${cell.column.id}`]: true,
                  'cp-table-cell--wrap': cell.column.columnDef.meta?.wrap,
                  'border-b-0': hideBottomBorder(rowIdx),
                },
                resolveMetaClasses(cell.column.columnDef.meta?.columnClass),
                resolveMetaClasses(cell.column.columnDef.meta?.cellClass),
              ]"
            >
              <slot name="cell" :cell="cell" :row="row" :index="cellIdx">
                <FlexRender :cell="cell" />
              </slot>
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
                <craft-empty
                  :label="t('No results')"
                  icon="empty-set"
                ></craft-empty>
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
  :deep(.cp-table-cell--header) {
    white-space: nowrap;
  }
  :deep(.cp-table-cell--header[aria-sort]) {
    &:hover,
    &:focus-within {
      background-color: var(--c-color-fill-loud);
      color: var(--c-color-on-loud);
    }
  }
  :deep(.cell--wrap) {
    white-space: normal;
  }
  :deep(.cp-table-cell) {
    width: min-content;
  }
  // Selection column hugs its checkbox rather than claiming a data-column share.
  // Doubled class so it outranks wrappers that restate the `min-content` width.
  :deep(.cp-table-cell.cp-table-cell--select) {
    width: 1px;
    white-space: nowrap;
  }
  :deep(.row--dragging) {
    opacity: 0.4;
  }

  // Selected rows take the accent fill a selected chip or thumbnail does, so a
  // selection reads the same whichever view mode you're in. Painted on the
  // cells rather than the row: a `<tr>` background loses to any the cells set.
  :deep(.cp-table-row.sel > td) {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-quiet);
  }

  // Cells carry a bottom border only, so a run of selected rows is bounded by
  // the bottom border of the row above it and the bottom border of its own last
  // row. Borders between selected rows are interior and stay quiet.
  :deep(.cp-table-row.sel:not(:has(+ .cp-table-row.sel)) > td) {
    border-block-end-color: var(--c-color-accent-border-normal);
  }

  :deep(.cp-table-row:not(.sel):has(+ .cp-table-row.sel) > td) {
    border-block-end-color: var(--c-color-accent-border-normal);
  }

  // Nothing above the first row to carry its edge, so it keeps its own.
  :deep(.cp-table-row.sel:first-child > td) {
    border-block-start-color: var(--c-color-accent-border-normal);
  }
</style>
