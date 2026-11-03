<script setup lang="ts">
  /**
   * A step's destination columns, as one table. A container column — a Matrix field,
   * say — has no mapping of its own; its row expands to show its own columns beneath it,
   * grouped by entry type when there's more than one.
   *
   * Everything but the columns is read from the mapping context around it.
   */
  import '@craftcms/ui/components/badge/badge';
  import {Color, t} from '@craftcms/ui';
  import {computed, h, inject} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import type {Row} from '@tanstack/vue-table';
  import {
    useCraftTable,
    type CraftTableFeatures,
  } from '@/common/table/craftTable';
  import MappingCell from './MappingCell.vue';
  import MappingContainerCell from './MappingContainerCell.vue';
  import {countMapped, getAt, isCompoundMappingColumn} from './paths';
  import {
    MappingContextKey,
    type MappingColumn,
    type MappingColumnEntry,
  } from './types';

  type MappingRowData =
    | {kind: 'column'; id: string; col: MappingColumn; heading: string | null}
    | {
        kind: 'container';
        id: string;
        col: MappingColumn;
        subRows?: MappingRowData[];
      }
    | {kind: 'group'; id: string; label: string; subRows: MappingRowData[]};

  type Part = 'destination' | 'incoming' | 'match' | 'clear';

  const props = defineProps<{
    cols: MappingColumnEntry[];
    /** Leaves out the Match column, for matching that's chosen somewhere else. */
    hideMatch?: boolean;
    /** Which columns to show, by whether they're mapped. */
    filter?: 'all' | 'mapped' | 'unmapped';
    /** Shows only the columns whose name or handle includes this. */
    query?: string;
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error('MappingTable must be rendered inside a mapping editor.');
  }

  /**
   * One row per column, with a compound column's subfields as rows of their own, each
   * carrying its heading. A container that's been loaded carries its own columns as
   * sub-rows.
   */
  function buildRows(cols: MappingColumnEntry[]): MappingRowData[] {
    return cols.flatMap((entry): MappingRowData[] => {
      if (isCompoundMappingColumn(entry)) {
        return entry.subfields.map((col) => ({
          kind: 'column',
          id: col.prefixedHandle,
          col,
          heading: entry.heading,
        }));
      }

      if (!entry.isContainer) {
        return [
          {kind: 'column', id: entry.prefixedHandle, col: entry, heading: null},
        ];
      }

      const groups = context!.nested[entry.prefixedHandle]?.groups ?? [];
      const grouped = groups.length > 1 || Boolean(groups[0]?.providerName);

      return [
        {
          kind: 'container',
          id: entry.prefixedHandle,
          col: entry,
          subRows: grouped
            ? groups.map(
                (group, index): MappingRowData => ({
                  kind: 'group',
                  id: `${entry.prefixedHandle}#${index}`,
                  label: group.providerName ?? entry.label,
                  subRows: buildRows(group.destinationCols),
                })
              )
            : buildRows(groups[0]?.destinationCols ?? []),
        },
      ];
    });
  }

  /** Whether a column row passes the search and the mapped filter. */
  function shows(col: MappingColumn, heading: string | null): boolean {
    const query = (props.query ?? '').trim().toLowerCase();
    const mapped = Boolean(
      getAt(context!.values.map, col.prefixedHandleAsArray)
    );

    if (props.filter === 'mapped' && !mapped) {
      return false;
    }

    if (props.filter === 'unmapped' && mapped) {
      return false;
    }

    return (
      !query ||
      `${heading ?? ''} ${col.label} ${col.handle}`
        .toLowerCase()
        .includes(query)
    );
  }

  /** The rows that pass the search and filter, and the containers and groups holding them. */
  function filterRows(list: MappingRowData[]): MappingRowData[] {
    return list.flatMap((row): MappingRowData[] => {
      if (row.kind === 'column') {
        return shows(row.col, row.heading) ? [row] : [];
      }

      const subRows = filterRows(row.subRows ?? []);

      if (subRows.length) {
        return [{...row, subRows}];
      }

      if (row.kind === 'group') {
        return [];
      }

      // a container whose own columns are yet to load is judged by what's mapped inside it
      const loaded = Boolean(context!.nested[row.id]?.groups.length);
      const mapped =
        countMapped(getAt(context!.values.map, row.col.prefixedHandleAsArray)) >
        0;
      const query = (props.query ?? '').trim().toLowerCase();
      const named =
        !query ||
        `${row.col.label} ${row.col.handle}`.toLowerCase().includes(query);
      const passes =
        props.filter === 'mapped'
          ? mapped
          : props.filter === 'unmapped'
            ? !loaded
            : true;

      return named && passes && (!query || !loaded) ? [{...row, subRows}] : [];
    });
  }

  const filtering = computed(
    () =>
      Boolean((props.query ?? '').trim()) || (props.filter ?? 'all') !== 'all'
  );

  const rows = computed(() =>
    filtering.value ? filterRows(buildRows(props.cols)) : buildRows(props.cols)
  );

  /** The rows on show: everything but what's inside a collapsed container. */
  const visibleRows = computed(() => {
    const visible: MappingRowData[] = [];
    const walk = (list: MappingRowData[]) => {
      for (const row of list) {
        visible.push(row);

        if (
          row.kind === 'group' ||
          (row.kind === 'container' && context.expanded.has(row.id))
        ) {
          walk(row.subRows ?? []);
        }
      }
    };
    walk(rows.value);

    return visible;
  });

  /** Expanded containers and the entry type groups inside them, keyed by row id. */
  const expandedState = computed(() =>
    Object.fromEntries(
      visibleRows.value
        .filter(
          (row) =>
            row.kind === 'group' ||
            (row.kind === 'container' && context.expanded.has(row.id))
        )
        .map((row) => [row.id, true])
    )
  );

  /** Whether any row on show was filled from a guess, for the legend that explains its badge. */
  const hasSuggestion = computed(
    () =>
      context.editable &&
      visibleRows.value.some(
        (row) =>
          row.kind === 'column' &&
          Boolean(getAt(context.suggestedMap, row.col.prefixedHandleAsArray))
      )
  );

  const columnHelper = createCraftColumnHelper<MappingRowData>();

  /**
   * Rows are aligned to their top, so a row that grows does so downward. Each cell's
   * content sits in a line a control's height, which keeps a short checkbox
   * centered on the controls beside it.
   */
  const lined = (content: ReturnType<typeof h> | null) =>
    h('div', {class: 'mapping-table__line'}, content ? [content] : []);

  const cell =
    (part: Part) =>
    ({row}: {row: {original: MappingRowData}}) => {
      const data = row.original;

      if (data.kind === 'column') {
        return lined(
          h(MappingCell, {
            col: data.col,
            heading: data.heading,
            part,
          })
        );
      }

      if (data.kind === 'group') {
        return part === 'destination'
          ? h('span', {class: 'mapping-table__group'}, data.label)
          : null;
      }

      return lined(
        part === 'destination' || part === 'incoming'
          ? h(MappingContainerCell, {col: data.col, part})
          : null
      );
    };

  /** Indents a container's own columns, entry type headings included, a step per container they're inside. */
  function rowAttributes(row: Row<CraftTableFeatures, MappingRowData>) {
    const level = row
      .getParentRows()
      .filter((parent) => parent.original.kind === 'container').length;

    return {
      class: {
        'mapping-table__row--nested': level > 0,
        'mapping-table__group-row': row.original.kind === 'group',
      },
      style: level > 0 ? {'--mapping-level': level} : undefined,
    };
  }

  const table = useCraftTable({
    get data() {
      return rows.value;
    },
    state: {
      get expanded() {
        return expandedState.value;
      },
    },
    getSubRows: (row) =>
      row.kind === 'container' || row.kind === 'group'
        ? row.subRows
        : undefined,
    getRowCanExpand: (row) =>
      row.original.kind === 'container' || row.original.kind === 'group',
    columns: columnHelper.columns([
      columnHelper.display({
        id: 'destination',
        header: t('Destination'),
        meta: {
          cellTag: 'th',
          wrap: true,
          columnClass: 'mapping-table__destination',
        },
        cell: cell('destination'),
      }),
      columnHelper.display({
        id: 'incoming',
        header: t('Incoming data'),
        meta: {wrap: true},
        cell: cell('incoming'),
      }),
      ...(props.hideMatch
        ? []
        : [
            columnHelper.display({
              id: 'match',
              header: t('Match'),
              meta: {
                columnClass: 'mapping-table__option',
                headerTip: t(
                  'Use this field’s value to match against an existing element.'
                ),
              },
              cell: cell('match'),
            }),
          ]),
      columnHelper.display({
        id: 'clear',
        header: t('Clear on empty'),
        meta: {
          columnClass: 'mapping-table__option',
          headerTip: t(
            'Clear existing value if no data provided or provided value is empty.'
          ),
        },
        cell: cell('clear'),
      }),
    ]),
    getRowId: (row) => row.id,
  });
</script>

<template>
  <div class="mapping-table">
    <AdminTable
      :table="table"
      :title="t('Mapping')"
      :show-footer="false"
      :row-attributes="rowAttributes"
    />
  </div>

  <div v-if="hasSuggestion" class="mapping-table__legends">
    <p v-if="hasSuggestion" class="mapping-table__legend">
      <craft-badge :fill="Color.Accent" size="small" no-prefix>{{
        t('Suggested')
      }}</craft-badge>
      {{
        t(
          'These selections were auto-selected as the closest matches from the incoming data. Change them if they’re not right.'
        )
      }}
    </p>
  </div>
</template>

<style scoped>
  .mapping-table {
    overflow-x: auto;
    /* edge to edge across whatever holds it, when that says how far its padding reaches */
    margin-inline: calc(var(--mapping-table-gutter, 0px) * -1);
  }

  .mapping-table :deep(.mapping-table__destination) {
    width: 25%;
  }

  /* a container's own columns sit under it, indented a step per level */
  .mapping-table
    :deep(.mapping-table__row--nested .mapping-table__destination) {
    padding-inline-start: calc(
      var(--_cell-spacing-inline, var(--c-spacing-sm)) + var(--mapping-level) *
        var(--c-spacing-xl) * 0.6
    );
    box-shadow: inset 3px 0 0 var(--c-color-neutral-border-quiet);
  }

  /* cells take the table's row height as their own, so a heading row sets its own */
  /* the space a control has above and below it in a row of the table's own height,
     kept when content makes the row taller */
  .mapping-table :deep(tbody tr:not(.mapping-table__group-row) > *) {
    vertical-align: top;
    padding-block: calc((var(--_row-height) - var(--c-size-control-md)) / 2);
  }

  .mapping-table :deep(.mapping-table__line) {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: var(--c-size-control-md);
  }

  .mapping-table :deep(.mapping-table__group-row > *) {
    height: calc(32rem / 16);
    padding-block: 0;
    background-color: var(--c-color-neutral-fill-quiet);
  }

  /* as the navigation's group headings are */
  .mapping-table :deep(.mapping-table__group) {
    font-size: var(--c-text-sm);
    font-weight: bold;
  }

  .mapping-table__legends {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    margin-block-start: var(--c-spacing-sm);
    padding-block-start: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .mapping-table__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
    margin: 0;
    color: var(--c-text-quiet);
    font-size: 0.85em;
  }

  .mapping-table :deep(.mapping-table__option) {
    width: 1%;
    white-space: nowrap;
  }
</style>
