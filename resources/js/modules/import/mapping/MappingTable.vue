<script setup lang="ts">
  /**
   * A run of destination columns. Rendered by both the map page and the nested
   * panel, which is why it takes the columns and reads everything else from the
   * mapping context around it.
   */
  import {t} from '@craftcms/ui';
  import type {Row} from '@tanstack/vue-table';
  import {computed, h} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {
    useCraftTable,
    type CraftTableFeatures,
  } from '@/common/table/craftTable';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import DestinationCell from './cells/DestinationCell.vue';
  import IncomingCell from './cells/IncomingCell.vue';
  import {
    checkedTreeValue,
    getAt,
    isChecked,
    isCompoundMappingColumn,
    isMappingColumn,
    setAt,
  } from './paths';
  import {
    useMappingContext,
    type MappingColumn,
    type MappingColumnEntry,
    type MappingValues,
  } from './types';

  type MappingRowData =
    | {kind: 'column'; id: string; col: MappingColumn}
    | {kind: 'heading'; id: string; label: string | null};

  const props = defineProps<{
    cols: MappingColumnEntry[];
  }>();

  const context = useMappingContext();

  /** A compound column's subfields follow a heading row of their own. */
  const rows = computed(() =>
    props.cols.flatMap((entry, index): MappingRowData[] => {
      if (isCompoundMappingColumn(entry)) {
        return [
          {kind: 'heading', id: `heading-${index}`, label: entry.heading},
          ...entry.subfields.map(
            (col): MappingRowData => ({
              kind: 'column',
              id: col.prefixedHandle,
              col,
            })
          ),
        ];
      }

      return isMappingColumn(entry)
        ? [{kind: 'column', id: entry.prefixedHandle, col: entry}]
        : [];
    })
  );

  function isMapped(col: MappingColumn): boolean {
    return isChecked(getAt(context.values.map, col.prefixedHandleAsArray));
  }

  /**
   * A Match or Clear column, each on its own tree. A container has no value of its
   * own to match on or clear: each of its nested columns decides that in its panel.
   */
  function optionColumn(options: {
    id: 'match' | 'clear';
    tree: 'matchCriteria' | 'clearableItems';
    header: string;
    headerTip: string;
    label: (col: MappingColumn) => string;
    applies: (col: MappingColumn) => boolean;
    disabled?: (col: MappingColumn) => boolean;
  }) {
    const tree = (): MappingValues[typeof options.tree] =>
      context.values[options.tree];

    return columnHelper.checkbox({
      id: options.id,
      header: options.header,
      meta: {
        columnClass: 'mapping-table__option',
        headerTip: options.headerTip,
      },
      hidden: (row) =>
        row.kind !== 'column' ||
        row.col.isContainer ||
        !options.applies(row.col),
      value: (row) =>
        row.kind === 'column' &&
        isChecked(getAt(tree(), row.col.prefixedHandleAsArray)),
      label: (row) => (row.kind === 'column' ? options.label(row.col) : ''),
      disabled: (row) =>
        !context.editable ||
        (row.kind === 'column' && Boolean(options.disabled?.(row.col))),
      onChange: (row, checked) => {
        if (row.kind === 'column') {
          setAt(
            tree(),
            row.col.prefixedHandleAsArray,
            checkedTreeValue(checked)
          );
        }
      },
    });
  }

  function rowAttributes(row: Row<CraftTableFeatures, MappingRowData>) {
    const data = row.original;

    return {
      class: {
        'mapping-table__heading-row': data.kind === 'heading',
        'best-guess':
          data.kind === 'column' &&
          Boolean(getAt(context.suggestedMap, data.col.prefixedHandleAsArray)),
      },
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
        meta: {
          cellTag: 'th',
          wrap: true,
          columnClass: 'mapping-table__destination',
        },
        cell: ({row}) =>
          row.original.kind === 'heading'
            ? h('strong', row.original.label ?? '')
            : h(DestinationCell, {col: row.original.col}),
      }),
      columnHelper.display({
        id: 'incoming',
        header: t('Incoming data'),
        meta: {wrap: true, columnClass: 'mapping-table__incoming'},
        cell: ({row}) =>
          row.original.kind === 'column'
            ? h(IncomingCell, {col: row.original.col})
            : null,
      }),
      optionColumn({
        id: 'match',
        tree: 'matchCriteria',
        header: t('Match'),
        headerTip: t(
          'Use this field’s value to match against an existing element.'
        ),
        label: (col) => t('Match on {field}', {field: col.label}),
        applies: (col) => Boolean(col.canBeMatchCriteria),
        // only a mapped column has a value to match on
        disabled: (col) => !isMapped(col),
      }),
      optionColumn({
        id: 'clear',
        tree: 'clearableItems',
        header: t('Clear'),
        headerTip: t(
          'Clear existing value if no data provided or provided value is empty.'
        ),
        label: (col) => t('Clear {field}', {field: col.label}),
        applies: (col) => Boolean(col.canBeCleared),
      }),
    ]),
  });
</script>

<template>
  <div class="mapping-table">
    <AdminTable
      :table="table"
      :title="t('Mapping')"
      :show-footer="false"
      :read-only="!context.editable"
      appearance="flush"
      :row-attributes="rowAttributes"
    />
  </div>
</template>

<style scoped>
  .mapping-table {
    overflow-x: auto;
  }

  .mapping-table :deep(.mapping-table__destination) {
    width: 20%;
    text-align: start;
  }

  .mapping-table :deep(.mapping-table__option) {
    width: 1%;
    white-space: nowrap;
  }

  /* rows grow downward as a column's template and settings open up beneath it */
  .mapping-table :deep(tbody td),
  .mapping-table :deep(tbody th) {
    vertical-align: top;
  }

  .mapping-table :deep(.best-guess .mapping-table__incoming) {
    background: var(--color-blue-100);
  }

  .mapping-table :deep(.mapping-table__heading-row > *) {
    background-color: var(--c-color-neutral-fill-quiet);
  }
</style>
