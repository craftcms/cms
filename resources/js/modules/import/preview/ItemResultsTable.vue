<script setup lang="ts" generic="T extends ItemResultRow">
  /**
   * A page of a step's items, each with what importing it does or would do: chips
   * to show only those with one result, then a row each with its result, title
   * and the values it was matched on, and a button to look closer.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {Color, t} from '@craftcms/ui';
  import {computed, h, useId, type VNodeChild} from 'vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {
    resultFills,
    resultLabels,
    type ItemAction,
    type ItemResultRow,
    type PreviewResult,
  } from './plan-preview';

  const props = defineProps<{
    items: T[];
    rowId: (item: T) => string;
    matchColumns: Array<{key: string; label: string}>;
    /** How many items have each result, for the chips. */
    counts: Record<PreviewResult, number> | null;
    /** How many items there are in all. */
    total: number;
    /** How many rows the chosen result pages through. */
    matching: number;
    /** The page's index. */
    page: number;
    pageSize: number;
    loading?: boolean;
    placeholderRows?: number;
    /** Whether a chip can be chosen; by default, when items have its result. */
    canFilter?: (result: PreviewResult) => boolean;
    /** The buttons at the end of an item's row, in order. */
    actions: (item: T) => ItemAction[];
    /** The most buttons a row has, which its column is as wide as. */
    actionCount?: number;
    /** Leaves out the chips and the result column, for a table of items that all have one result. */
    oneResult?: boolean;
    /** Lists items' problems in a column of their own, rather than under their titles. */
    problemsColumn?: boolean;
    /** Runs the table edge to edge of its container, inset by `--cp-container-padding`. */
    flush?: boolean;
    /** Where an item's title links to, which also sets titles in bold. */
    titleUrl?: (item: T) => string | null | undefined;
  }>();

  const filter = defineModel<PreviewResult | 'all'>('filter', {
    default: 'all',
  });

  const emit = defineEmits<{
    page: [index: number];
  }>();

  const id = useId();

  const filters = computed(() =>
    (['all', ...Object.keys(resultLabels)] as Array<PreviewResult | 'all'>).map(
      (value) => {
        const count =
          value === 'all' ? props.total : (props.counts?.[value] ?? 0);

        return {
          value,
          count,
          label: value === 'all' ? t('All') : resultLabels[value],
          enabled:
            value === 'all' ||
            (props.canFilter ? props.canFilter(value) : count > 0),
        };
      }
    )
  );

  const countFormat = new Intl.NumberFormat(
    document.documentElement.lang || undefined
  );

  /** Each match column's share of the table, which together leave the title at least as much as they take. */
  const matchColumnWidth = computed(
    () => `${Math.min(35, 50 / Math.max(1, props.matchColumns.length))}%`
  );

  function buttonId(item: T, action: ItemAction): string {
    return `${id}-${action.icon}-${props.rowId(item)}`;
  }

  /** A cell's first line, as tall as a result's badge, so each cell's first line is centered on the badge's. */
  function line(content: VNodeChild) {
    return h('span', {class: 'item-results__line'}, [content]);
  }

  function problemList(problems: string[]) {
    return h(
      'ul',
      {class: 'item-results__problems'},
      problems.map((problem) => h('li', problem))
    );
  }

  function titleCell(item: T) {
    if (!item.title) {
      return h('span', {class: 'text-quiet'}, t('Untitled'));
    }

    if (!props.titleUrl) {
      return item.title;
    }

    const url = props.titleUrl(item);

    return url
      ? h('a', {href: url, class: 'font-bold'}, item.title)
      : h('span', {class: 'font-bold'}, item.title);
  }

  const columnHelper = createCraftColumnHelper<T>();

  function resultColumn() {
    return columnHelper.display({
      id: 'result',
      header: t('Result'),
      meta: {columnClass: 'item-results__result-col'},
      cell: ({row}) =>
        line(
          h(
            'craft-badge',
            {fill: resultFills[row.original.result], 'no-prefix': ''},
            resultLabels[row.original.result]
          )
        ),
    });
  }

  const columns = computed(() =>
    columnHelper.columns([
      ...(props.oneResult ? [] : [resultColumn()]),
      columnHelper.display({
        id: 'title',
        header: t('Title'),
        meta: {columnClass: 'item-results__title-col'},
        cell: ({row}) => {
          const title = line(titleCell(row.original));
          const problems = row.original.problems ?? [];

          return problems.length && !props.problemsColumn
            ? h('div', [title, problemList(problems)])
            : title;
        },
      }),
      ...props.matchColumns.map((column) =>
        columnHelper.display({
          id: `match:${column.key}`,
          header: () =>
            h('span', {class: 'item-results__heading'}, [
              column.label,
              h(
                'craft-badge',
                {size: 'small', fill: Color.Accent, 'no-prefix': ''},
                t('Match key')
              ),
            ]),
          meta: {columnClass: 'item-results__match-col'},
          cell: ({row}) =>
            row.original.match[column.key]
              ? line(h('code', row.original.match[column.key]))
              : null,
        })
      ),
      ...(props.problemsColumn
        ? [
            columnHelper.display({
              id: 'problems',
              header: t('Problems'),
              meta: {columnClass: 'item-results__problems-col'},
              cell: ({row}) =>
                row.original.problems?.length
                  ? problemList(row.original.problems)
                  : null,
            }),
          ]
        : []),
      columnHelper.display({
        id: 'actions',
        header: () => h('span', {class: 'sr-only'}, t('Actions')),
        meta: {
          columnClass: 'item-results__actions-col',
          cellClass: 'item-results__actions',
        },
        cell: ({row}) => {
          const actions = props.actions(row.original);

          return actions.length
            ? h(
                'span',
                {class: 'item-results__line item-results__line--end'},
                actions.flatMap((action) => [
                  h('craft-button', {
                    id: buttonId(row.original, action),
                    type: 'button',
                    icon: action.icon,
                    variant: 'plain',
                    size: 'small',
                    'aria-label': action.label,
                    loading: action.loading ?? false,
                    onClick: action.onClick,
                  }),
                  h(
                    'craft-tooltip',
                    {for: buttonId(row.original, action)},
                    action.tooltip
                  ),
                ])
              )
            : null;
        },
      }),
    ])
  );

  // paged by whoever has the items, which the table's own pagination state follows
  const table = useCraftTable({
    get data() {
      return props.items;
    },
    getRowId: (row) => props.rowId(row),
    get columns() {
      return columns.value;
    },
    manualPagination: true,
    get rowCount() {
      return props.matching;
    },
    state: {
      get pagination() {
        return {pageIndex: props.page, pageSize: props.pageSize};
      },
    },
    onPaginationChange: (updater) => {
      const next =
        typeof updater === 'function'
          ? updater({pageIndex: props.page, pageSize: props.pageSize})
          : updater;
      emit('page', next.pageIndex);
    },
  });
</script>

<template>
  <div class="item-results" :class="{'item-results--flush': flush}">
    <div
      v-if="!oneResult"
      role="group"
      :aria-label="t('Show items')"
      class="item-results__chips"
    >
      <button
        v-for="chip in filters"
        :key="chip.value"
        type="button"
        class="item-results__chip"
        :aria-pressed="filter === chip.value ? 'true' : 'false'"
        :disabled="!chip.enabled"
        @click="filter = chip.value"
      >
        <craft-badge
          :fill="chip.value === 'all' ? Color.Gray : resultFills[chip.value]"
          no-prefix
          >{{ chip.label }}
          <span v-if="counts" slot="suffix" class="item-results__chip-count">{{
            countFormat.format(chip.count)
          }}</span></craft-badge
        >
      </button>
    </div>
    <slot name="notice"></slot>
    <AdminTable
      class="item-results__table"
      :style="{
        '--item-results-match-width': matchColumnWidth,
        '--item-results-actions': actionCount ?? 1,
      }"
      :table="table"
      :title="t('Items')"
      :appearance="flush ? 'flush' : undefined"
      layout="fixed"
      spacing="compact"
      :loading="loading"
      :placeholder-rows="loading ? (placeholderRows ?? 0) : 0"
      :from="page * pageSize + 1"
      :to="page * pageSize + items.length"
      :total="matching"
    >
      <template v-if="$slots.empty" #empty-row>
        <slot name="empty"></slot>
      </template>
    </AdminTable>
  </div>
</template>

<style scoped>
  .item-results {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  /* fixed at every width, so the columns don't move as the filters and pages change what's in them */
  .item-results__table :deep(table) {
    table-layout: fixed;
    min-inline-size: 36rem;
  }

  .item-results__table :deep(.item-results__title-col) {
    width: auto;
  }

  .item-results__table :deep(.item-results__match-col) {
    width: var(--item-results-match-width);
  }

  /* as wide as the widest result's badge */
  .item-results__table :deep(.item-results__result-col) {
    width: 8rem;
  }

  .item-results__table :deep(.item-results__actions-col) {
    width: calc(var(--item-results-actions) * 2.25rem + 0.75rem);
  }

  .item-results--flush > :not(.item-results__table) {
    padding-inline: var(--cp-container-padding);
  }

  .item-results--flush .item-results__table :deep(:is(th, td):first-child) {
    padding-inline-start: var(--cp-container-padding);
  }

  .item-results--flush .item-results__table :deep(:is(th, td):last-child) {
    padding-inline-end: var(--cp-container-padding);
  }

  /* a row's cells line up on their first line, which an item's problems go below */
  .item-results__table :deep(tbody td) {
    vertical-align: top;
  }

  /* placeholders are one line each, so they sit centred in their rows */
  .item-results__table :deep(tbody tr[aria-hidden='true'] td) {
    vertical-align: middle;
  }

  .item-results__table :deep(.item-results__line) {
    display: flex;
    align-items: center;
    min-block-size: calc(28rem / 16);
    min-inline-size: 0;
  }

  .item-results__table :deep(.item-results__line--end) {
    justify-content: flex-end;
  }

  .item-results__table :deep(.item-results__problems-col) {
    width: auto;
  }

  .item-results__table :deep(.item-results__problems) {
    display: grid;
    gap: var(--c-spacing-xs);
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
    padding: 0;
    list-style: none;
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
    white-space: normal;
  }

  .item-results__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
  }

  /* the same badge the table shows each result in, as a button */
  .item-results__chip {
    display: inline-flex;
    padding: 0;
    font: inherit;
    background: none;
    border: 0;
    border-radius: var(--c-radius-full);
    cursor: pointer;
  }

  .item-results__chip:hover:not(:disabled, [aria-pressed='true'])
    craft-badge::part(badge) {
    border-color: var(--c-color-border-loud);
  }

  .item-results__chip[aria-pressed='true'] craft-badge::part(badge) {
    color: var(--c-color-on-loud);
    background-color: var(--c-color-fill-loud);
    border-color: transparent;
  }

  .item-results__chip:disabled {
    cursor: default;
    opacity: 0.5;
  }

  .item-results__chip-count {
    font-variant-numeric: tabular-nums;
    opacity: 0.75;
  }

  .item-results__table :deep(.item-results__heading) {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  /* cells clip their text, which would clip the button's focus ring along the table's edge */
  .item-results__table :deep(.item-results__actions) {
    text-align: end;
    overflow: visible;
  }

  /* in a column of their own, they start on the row's first line, and read at the table's size */
  .item-results__table
    :deep(.item-results__problems-col .item-results__problems) {
    margin-block-start: calc((calc(28rem / 16) - 1lh) / 2);
    font-size: inherit;
  }
</style>
