<script setup lang="ts">
  /**
   * Destination columns and how each is filled. Rendered by both the step's mapping and
   * the nested panel, which is why it takes the columns and reads everything else from
   * the mapping context around it.
   */
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import type {Row} from '@tanstack/vue-table';
  import {h} from 'vue';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import AdminTableToolbar from '@/modules/admin-table/components/AdminTableToolbar.vue';
  import {
    useCraftTable,
    type CraftTableFeatures,
  } from '@/common/table/craftTable';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import DestinationCell from './cells/DestinationCell.vue';
  import IncomingCell from './cells/IncomingCell.vue';
  import MatchCell from './cells/MatchCell.vue';
  import PreviewCell from './cells/PreviewCell.vue';
  import WhenEmptyCell from './cells/WhenEmptyCell.vue';
  import {emptyValueModes} from './empty-values';
  import {isMatchKey} from './matching';
  import {getAt, isChecked} from './paths';
  import {
    useMappingContext,
    type MappingColumn,
    type MappingColumnEntry,
  } from './types';
  import {
    useMappingRows,
    type MappingTableGroup,
    type MappingRowData,
  } from './useMappingRows';

  const props = defineProps<{
    cols?: MappingColumnEntry[];
    /** Set when the table sits inside a padded container, rather than spanning a panel edge to edge. */
    inset?: boolean;
    /** Several runs of columns in one table, in place of `cols`. */
    groups?: MappingTableGroup[];
  }>();

  const context = useMappingContext();

  const {search, rows} = useMappingRows(
    () => props.groups ?? [{label: null, cols: props.cols ?? []}]
  );

  /**
   * Only a mapped column has an empty value to handle, and one with a single choice has
   * nothing to pick. Nor does a column rows are matched on: an empty value matches nothing.
   */
  function hasEmptyValueChoice(col: MappingColumn): boolean {
    return (
      !col.isContainer &&
      isChecked(getAt(context.values.map, col.prefixedHandleAsArray)) &&
      !isMatchKey(context.values, col) &&
      emptyValueModes(col).length > 1
    );
  }

  function rowAttributes(row: Row<CraftTableFeatures, MappingRowData>) {
    return {
      class: {'mapping-table__heading-row': row.original.kind === 'heading'},
    };
  }

  const columnHelper = createCraftColumnHelper<MappingRowData>();

  const table = useCraftTable({
    get data() {
      return rows.value;
    },
    getRowId: (row) => row.id,
    columns: columnHelper.columns([
      columnHelper.display({
        id: 'destination',
        header: t('Destination'),
        meta: {cellTag: 'th', wrap: true, columnClass: 'mapping-table__main'},
        cell: ({row}) =>
          row.original.kind === 'heading'
            ? h('strong', row.original.label)
            : h(DestinationCell, {col: row.original.col}),
      }),
      columnHelper.display({
        id: 'incoming',
        header: t('Incoming data'),
        meta: {
          wrap: true,
          columnClass: 'mapping-table__main',
          cellClass: 'mapping-table__cell--incoming',
        },
        cell: ({row}) =>
          row.original.kind === 'column'
            ? h(IncomingCell, {col: row.original.col})
            : null,
      }),
      columnHelper.display({
        id: 'preview',
        header: t('Preview'),
        meta: {
          wrap: true,
          cellClass: 'mapping-table__cell--preview',
          headerTip: t(
            'The first item’s value, or the template it’s filled from.'
          ),
        },
        cell: ({row}) =>
          row.original.kind === 'column'
            ? h('div', {class: 'mapping-table__preview-box'}, [
                h(PreviewCell, {col: row.original.col}),
              ])
            : null,
      }),
      columnHelper.display({
        id: 'match',
        header: t('Match'),
        meta: {
          columnClass: 'thin',
          cellClass: 'mapping-table__cell--match',
          headerTip: t(
            'Existing elements are found by the ticked fields, to update them.'
          ),
        },
        cell: ({row}) =>
          row.original.kind === 'column'
            ? h(MatchCell, {col: row.original.col})
            : null,
      }),
      columnHelper.display({
        id: 'whenEmpty',
        header: t('When empty'),
        meta: {
          cellClass: 'mapping-table__cell--whenEmpty',
          headerTip: t('What to do when an item’s value is missing or empty.'),
        },
        cell: ({row}) =>
          row.original.kind === 'column' &&
          hasEmptyValueChoice(row.original.col)
            ? h(WhenEmptyCell, {col: row.original.col})
            : h('div', {class: 'mapping-table__when-empty-space'}),
      }),
    ]),
  });
</script>

<template>
  <AdminTable
    class="mapping-table"
    :table="table"
    :title="t('Mapping')"
    :show-footer="false"
    :read-only="!context.editable"
    :appearance="inset ? 'plain' : 'flush'"
    :row-attributes="rowAttributes"
  >
    <template #table-header>
      <AdminTableToolbar>
        <template #search>
          <CraftInputSearch
            v-model="search"
            :label="t('Search fields')"
            label-sr-only
          />
        </template>
      </AdminTableToolbar>
    </template>
    <template #empty-row>
      <span class="text-quiet">{{ t('No fields match.') }}</span>
    </template>
  </AdminTable>
</template>

<style scoped>
  /* a narrow slideout scrolls the table sideways rather than squeezing its columns */
  .mapping-table :deep(table.cp-table) {
    min-inline-size: 52rem;
  }

  /* as wide as a When empty button whether or not a row has one, so ticking Match (which hides it) moves nothing */
  .mapping-table :deep(.mapping-table__when-empty-space) {
    inline-size: 11rem;
  }

  /* the other columns fit their content; these two share what's left */
  .mapping-table :deep(.mapping-table__main) {
    width: 50%;
    text-align: start;
  }

  /* a box rather than a column width, which the two shares above would win over */
  .mapping-table :deep(.mapping-table__preview-box) {
    inline-size: 280px;
  }

  /* a cell that grows, such as a template with its tip, leaves the others at the top */
  .mapping-table :deep(tbody :is(td, th)) {
    vertical-align: top;
  }

  /* text starts below its line's top, so a control starts level with the destination's lettering */
  .mapping-table
    :deep(
      :is(
        .mapping-table__cell--match,
        .mapping-table__cell--incoming,
        .mapping-table__cell--preview,
        .mapping-table__cell--whenEmpty
      )
    ) {
    padding-block-start: calc(var(--_cell-spacing-block) + (1lh - 1cap) / 2);
  }

  /* only a label, so it takes the height of its text rather than of a control */
  .mapping-table :deep(.mapping-table__heading-row > *) {
    height: auto;
    vertical-align: middle;
    background-color: var(--c-color-neutral-fill-quiet);
  }
</style>
