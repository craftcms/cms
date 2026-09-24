<script setup lang="ts">
  import {
    type Column,
    FlexRender,
    type Row,
    type Table,
  } from '@tanstack/vue-table';
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
  import {useReorderableRows} from '@/modules/admin-table/composables/useReorderableRows';
  import type {StructureMove} from '@/modules/elements/composables/useElementIndexStructure';
  import {
    type StructureDropType,
    useStructureDrag,
  } from '@/modules/elements/composables/useStructureDrag';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';
  import ColumnHeaderTitle from '@/modules/admin-table/components/ColumnHeaderTitle.vue';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import Empty from '@/common/components/Empty.vue';
  import {usePage} from '@inertiajs/vue3';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
  import {
    isInteractiveItemEvent,
    type ElementIndexItemBehavior,
  } from '@/modules/elements/types/item-behavior';

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
      /**
       * Indents rows by their `level` and adds a leading column of
       * expand/collapse toggles for rows with descendants.
       */
      structure?: boolean;
      isRowCollapsed?: (id: string | number) => boolean;
      /** Whether a row's expand/collapse request is still in flight. */
      isRowPending?: (id: string | number) => boolean;
      /**
       * Whether a structure row can make a given move. With `reorderable`,
       * structure rows reorder as a tree and emit `moveStructureRow`.
       */
      canMoveRow?: (id: string | number, move: StructureMove) => boolean;
      itemBehavior?: ElementIndexItemBehavior<any>;
      withBottomBorder?: boolean;
    }>(),

    {
      reorderable: false,
      selectable: false,
      loading: false,
      layout: 'auto',
      structure: false,
      isRowCollapsed: () => false,
      isRowPending: () => false,
      canMoveRow: () => false,
      withBottomBorder: true,
    }
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    toggleStructure: [id: string | number];
    moveStructureRow: [id: string | number, move: StructureMove];
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

  function onRowClick(row: any, event: MouseEvent) {
    if (props.itemBehavior?.onClick?.(row.original, event)) {
      return;
    }

    selectRowFromEvent(row, event);
  }

  const {setRowRef, setHandleRef, getDragState, getDropState} =
    useReorderableRows({
      getRowIds: () => props.table.getRowModel().rows.map((row: any) => row.id),
      onReorder: (startIndex, finishIndex) => {
        emit('reorder', startIndex, finishIndex);
      },
      enabled: () => !props.readOnly && props.reorderable && !props.structure,
    });

  const structureDropMoves: Partial<
    Record<StructureDropType, StructureMove['type']>
  > = {
    'reorder-above': 'before',
    'reorder-below': 'after',
    'make-child': 'child',
  };

  const structureDrag = useStructureDrag({
    getRows: () =>
      props.table.getRowModel().rows.map((row: any) => row.original),
    enabled: () => !readOnly.value && props.reorderable && props.structure,
    onMove: (id, move) => emit('moveStructureRow', id, move),
    blockedDrops: (sourceId, targetId) =>
      (
        Object.entries(structureDropMoves) as Array<
          [StructureDropType, 'before' | 'after' | 'child']
        >
      )
        .filter(([, type]) => !props.canMoveRow(sourceId, {type, targetId}))
        .map(([dropType]) => dropType),
  });

  function structureDropEdge(rowId: string | number) {
    const instruction = structureDrag.instructionFor(rowId);
    switch (instruction?.type) {
      case 'reorder-above':
        return 'top';
      case 'reorder-below':
      case 'reparent':
        return 'bottom';
      default:
        return null;
    }
  }

  function structurePosition(id: string | number) {
    const up = props.canMoveRow(id, {type: 'up'});
    const down = props.canMoveRow(id, {type: 'down'});
    if (!up && !down) return 'only';
    if (!up) return 'first';
    if (!down) return 'last';
    return 'middle';
  }

  function onStructureReorder(
    id: string | number,
    event: CustomEvent<{direction: NestedReorderDirection}>
  ) {
    emit('moveStructureRow', id, {type: event.detail.direction});
  }

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
    if (!(active instanceof HTMLElement)) return null;
    const headerCell = active.closest<HTMLElement>('th[id^="header-"]');
    return headerCell ? headerCell.id.slice('header-'.length) : null;
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

    if (props.structure) {
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

    // Leading utility columns, in render order: structure toggle, reorder
    // handle, then select.
    if (props.selectable) {
      gridDef.unshift('44px');
    }

    if (props.reorderable) {
      gridDef.unshift('44px');
    }

    if (props.structure) {
      gridDef.unshift('44px');
    }

    return {
      '--table-column-count': columnCount,
      '--table-template-columns': gridDef.join(' '),
    };
  });

  function rowLabel(row: Row<any>): string {
    return row.original.label ?? String(row.original.id);
  }

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
    row: any,
    index: number | string,
    event: KeyboardEvent
  ) {
    if (!props.selectable) return;
    if (isInteractiveItemEvent(event)) return;
    const rows = props.table.getRowModel().rows;
    if (!(event.currentTarget instanceof HTMLElement)) return;
    const target = event.currentTarget;
    index = Number(index);
    if (props.itemBehavior?.onKeydown?.(row.original, event)) {
      event.preventDefault();
      return;
    }
    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
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
        <th
          v-if="structure"
          class="cp-table-cell cp-table-cell--header cp-table-cell--structure"
          scope="col"
        >
          <span class="sr-only">{{ t('Expand or collapse') }}</span>
        </th>
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
              @sort-column="header.column.getToggleSortingHandler()?.($event)"
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
          :ref="
            (el) => {
              setRowRef(el as HTMLTableRowElement, row.id);
              structureDrag.setRowRef(el as Element | null, row.id);
            }
          "
          :tabindex="selectable ? 0 : undefined"
          v-bind="itemBehavior?.attrs?.(row.original)"
          :style="
            structure
              ? {'--structure-level': row.original.level ?? 1}
              : undefined
          "
          :class="{
            row: true,
            'cp-table-row': true,
            sel: row.getIsSelected(),
            'row--dragging':
              !readOnly &&
              (getDragState(row.id).type === 'is-dragging' ||
                structureDrag.isDragging(row.id)),
            'row--drop-child':
              structureDrag.instructionFor(row.id)?.type === 'make-child',
          }"
          @click="onRowClick(row, $event)"
          @keydown="onRowKeydown(row, rowIdx, $event)"
        >
          <td
            v-if="structure"
            class="cp-table-cell cp-table-cell--structure"
            :class="{'border-b-0': hideBottomBorder(rowIdx)}"
          >
            <craft-button
              v-if="row.original.hasDescendants"
              class="cp-table-structure-toggle"
              type="button"
              variant="plain"
              size="small"
              icon
              :loading="isRowPending(row.original.id)"
              :aria-expanded="String(!isRowCollapsed(row.original.id))"
              @click.stop="emit('toggleStructure', row.original.id)"
            >
              <craft-icon
                :name="
                  isRowCollapsed(row.original.id)
                    ? 'chevron-right'
                    : 'chevron-down'
                "
                :label="
                  isRowCollapsed(row.original.id)
                    ? t('Expand {title}', {title: rowLabel(row)})
                    : t('Collapse {title}', {title: rowLabel(row)})
                "
              ></craft-icon>
            </craft-button>

            <!-- Drop indicator spans entire row, positioned from this cell -->
            <DropIndicator
              v-if="reorderable && !readOnly"
              :edge="structureDropEdge(row.id)"
            />
          </td>
          <td
            v-if="reorderable && !readOnly && structure"
            class="cp-table-cell cp-table-cell--structure-reorder"
            :class="{'border-b-0': hideBottomBorder(rowIdx)}"
          >
            <div>
              <craft-reorder-button
                nested
                :position="structurePosition(row.original.id)"
                .canIndent="canMoveRow(row.original.id, {type: 'indent'})"
                .canOutdent="canMoveRow(row.original.id, {type: 'outdent'})"
                :ref="(el: any) => structureDrag.setHandleRef(el, row.id)"
                @craft-reorder="onStructureReorder(row.original.id, $event)"
              ></craft-reorder-button>
            </div>
          </td>
          <template v-else-if="reorderable && !readOnly">
            <td :class="{'border-b-0': hideBottomBorder(rowIdx)}">
              <div>
                <craft-reorder-button
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
          <td
            v-if="selectable"
            :class="{
              'cp-table-cell': true,
              'cp-table-cell--select': true,
              'border-b-0': hideBottomBorder(rowIdx),
            }"
          >
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
            <div v-if="structure && cellIdx === 0" class="cp-table-structure">
              <span class="sr-only"
                >{{ t('Level {level}', {level: row.original.level ?? 1}) }}
              </span>
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </div>
            <FlexRender
              v-else
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
  :deep(.cp-table-cell--select) {
    width: 1px;
    // max-width: calc(30rem / 16);
    white-space: nowrap;
  }

  :deep(.cp-table-cell--title) {
    width: 45%;
    min-width: 14rem;
  }

  .cp-table-row {
    --_structure-indent: calc(44px * (var(--structure-level, 1) - 1));
  }

  // Holds the toggle's footprint whether or not this row has one: the table
  // isn't in grid mode, so column widths come from content, and a collapsed
  // empty column would jump the moment a row first gained children.
  :deep(.cp-table-cell--structure) {
    inline-size: var(--c-size-control-sm);
    min-inline-size: var(--c-size-control-sm);
  }

  :deep(.cp-table-cell--structure-reorder),
  :deep(.cp-table-cell--structure) {
    padding-inline: 0;
  }

  :deep(.cp-table-cell--structure),
  :deep(.cp-table-cell--structure-reorder),
  :deep(.cp-table-cell--structure ~ .cp-table-cell--select) {
    overflow: visible;
  }

  :deep(.cp-table-structure-toggle),
  :deep(.cp-table-cell--structure-reorder > div),
  :deep(.cp-table-cell--structure ~ .cp-table-cell--select > craft-checkbox) {
    position: relative;
    z-index: 1;
    inset-inline-start: var(--_structure-indent);
  }

  :deep(.cp-table-structure-toggle:dir(rtl) craft-icon[name='chevron-right']) {
    transform: scaleX(-1);
  }

  :deep(.cp-table-structure) {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    padding-inline-start: var(--_structure-indent);
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

  :deep(.row--drop-child > td) {
    background-color: var(--c-color-accent-fill-quiet);
  }

  .cp-table-row[data-is-folder] {
    cursor: pointer;
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
