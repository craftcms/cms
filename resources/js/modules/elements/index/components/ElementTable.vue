<script setup lang="ts" generic="TData extends Record<string, any>">
  import {
    FlexRender,
    type CellContext,
    type Row,
    type Table,
  } from '@tanstack/vue-table';
  import type {CraftTableFeatures} from '@/common/table/craftTable';
  import {t} from '@craftcms/ui';
  import {computed, type HTMLAttributes, type VNodeChild} from 'vue';
  import DataTable from '@/common/components/DataTable.vue';
  import type {TableRowSelection} from '@/common/composables/useTableRowSelection';
  import type {NestedReorderDirection} from '@craftcms/ui';
  import type {StructureMove} from '@/modules/elements/index/composables/useElementIndexStructure';
  import {
    type StructureDropType,
    useStructureDrag,
  } from '@/modules/elements/index/composables/useStructureDrag';
  import {TableSpacing, type TableSpacingValue} from '@/common/types';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';

  const props = withDefaults(
    defineProps<{
      table: Table<CraftTableFeatures, TData>;
      selection: TableRowSelection<TData>;
      title?: string;
      reorderable?: boolean;
      selectable?: boolean;
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
      /**
       * Pads each row's outer cells by `--cp-container-padding`; see
       * `DataTable`'s `flush`.
       */
      flush?: boolean;
      /**
       * Disables selection, sorting and reordering while a request runs,
       * keeping the controls in place so the layout doesn't shift.
       */
      interactionsDisabled?: boolean;
      inlineEditing?: boolean;
      renderCell?: (
        context: CellContext<CraftTableFeatures, TData, unknown>,
        showErrors: boolean
      ) => unknown;
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
      flush: true,
      inlineEditing: false,
    }
  );

  const emit = defineEmits<{
    toggleStructure: [id: string | number];
    moveStructureRow: [id: string | number, move: StructureMove];
    reorder: [startIndex: number, finishIndex: number];
  }>();

  const readOnly = computed(() => props.selection.readOnly.value);
  function renderInlineCell(
    context: CellContext<CraftTableFeatures, TData, unknown>
  ): VNodeChild {
    const showErrors = context.row.getVisibleCells()[0]?.id === context.cell.id;

    return props.renderCell?.(context, showErrors) as VNodeChild;
  }

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
    enabled: () =>
      !readOnly.value &&
      props.reorderable &&
      props.structure &&
      !props.interactionsDisabled,
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

  const leadingColumnTracks = computed(() => [
    ...(props.structure ? ['44px'] : []),
    ...(props.structure && props.reorderable && !readOnly.value
      ? ['44px']
      : []),
  ]);

  function rowAttributes(row: Row<CraftTableFeatures, TData>): HTMLAttributes {
    return {
      ...props.itemBehavior?.attrs?.(row.original),
      style: props.structure
        ? {'--structure-level': row.original.level ?? 1}
        : undefined,
      class: {
        'row--dragging': structureDrag.isDragging(row.id),
        'row--drop-child':
          structureDrag.instructionFor(row.id)?.type === 'make-child',
      },
    };
  }

  function rowLabel(row: Row<CraftTableFeatures, TData>): string {
    return row.original.label ?? String(row.original.id);
  }
</script>

<template>
  <DataTable
    :table="table"
    :selection="selectable ? selection : undefined"
    :title="title"
    :loading="loading"
    :read-only="readOnly"
    :layout="layout"
    :spacing="spacing"
    :with-bottom-border="withBottomBorder"
    :flush="flush"
    :reorderable="reorderable && !structure"
    :interactions-disabled="interactionsDisabled"
    :leading-column-tracks="leadingColumnTracks"
    :row-attributes="rowAttributes"
    :row-behavior="itemBehavior"
    @row-ref="(el, row) => structureDrag.setRowRef(el, row.id)"
    @reorder="(from, to) => emit('reorder', from, to)"
  >
    <template #leading-header>
      <th
        v-if="structure"
        class="cp-table-cell cp-table-cell--header cp-table-cell--structure"
        scope="col"
      >
        <span class="sr-only">{{ t('Expand or collapse') }}</span>
      </th>
      <th
        v-if="structure && !readOnly && reorderable"
        scope="col"
        class="cell cell--header"
      >
        <span class="sr-only">{{ t('Reorder') }}</span>
      </th>
    </template>
    <template #leading-cells="{row, hideBottomBorder}">
      <td
        v-if="structure"
        class="cp-table-cell cp-table-cell--structure"
        :class="{'border-b-0': hideBottomBorder}"
      >
        <craft-button
          v-if="row.original.hasDescendants"
          class="cp-table-structure-toggle"
          type="button"
          variant="plain"
          size="small"
          icon
          .loading="isRowPending(row.original.id)"
          :aria-expanded="String(!isRowCollapsed(row.original.id))"
          @click.stop="emit('toggleStructure', row.original.id)"
        >
          <craft-icon
            :name="
              isRowCollapsed(row.original.id) ? 'chevron-right' : 'chevron-down'
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
        :class="{'border-b-0': hideBottomBorder}"
      >
        <div>
          <craft-reorder-button
            nested
            .disabled="interactionsDisabled"
            :position="structurePosition(row.original.id)"
            .canIndent="canMoveRow(row.original.id, {type: 'indent'})"
            .canOutdent="canMoveRow(row.original.id, {type: 'outdent'})"
            :ref="(el: any) => structureDrag.setHandleRef(el, row.id)"
            @craft-reorder="onStructureReorder(row.original.id, $event)"
          ></craft-reorder-button>
        </div>
      </td>
    </template>
    <template #cell="{cell, row, index}">
      <FlexRender
        v-if="inlineEditing && renderCell"
        :render="renderInlineCell"
        :props="cell.getContext()"
      />
      <div v-else-if="structure && index === 0" class="cp-table-structure">
        <span class="sr-only"
          >{{ t('Level {level}', {level: row.original.level ?? 1}) }}
        </span>
        <FlexRender :cell="cell" />
      </div>
      <FlexRender v-else :cell="cell" />
    </template>
    <template #empty-row v-if="$slots['empty-row']"
      ><slot name="empty-row"
    /></template>
  </DataTable>
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
</style>
