<script setup lang="ts">
  /**
   * One step's first rows: what each would do to the element it imports into, and the values
   * it fills each mapped destination with.
   */
  import '@craftcms/ui/components/badge/badge';
  import {computed, h} from 'vue';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {useCraftTable} from '@/common/table/craftTable';
  import type {StepReview} from './review';

  const props = defineProps<{
    review: StepReview;
    /** Names the table for assistive technology. */
    label: string;
  }>();

  type Row = StepReview['rows'][number];

  const helper = createCraftColumnHelper<Row>();

  function changeCell(row: Row) {
    if (row.action === 'create') {
      return h('craft-badge', {fill: 'green', size: 'small'}, t('Create'));
    }

    if (row.action === 'skip') {
      return h('craft-badge', {fill: 'gray', size: 'small'}, t('Skip'));
    }

    return h('span', {class: 'import-step-review__update'}, [
      h('craft-badge', {fill: 'blue', size: 'small'}, t('Update')),
      row.url
        ? h(
            'a',
            {href: row.url, target: '_blank', rel: 'noopener'},
            row.label ?? ''
          )
        : (row.label ?? ''),
    ]);
  }

  const table = useCraftTable({
    get data() {
      return props.review.rows;
    },
    get columns() {
      const isElementStep = props.review.created !== null;

      return helper.columns([
        helper.display({
          id: 'row',
          header: t('Row'),
          meta: {cellTag: 'th', columnClass: 'thin'},
          cell: ({row}) => String(row.original.row),
        }),
        ...(isElementStep
          ? [
              helper.display({
                id: 'change',
                header: t('Change'),
                meta: {wrap: true},
                cell: ({row}) => changeCell(row.original),
              }),
            ]
          : []),
        ...props.review.columns.map((column) =>
          helper.display({
            id: column.key,
            header: column.label,
            meta: {wrap: true},
            cell: ({row}) => row.original.values[column.key] ?? '',
          })
        ),
      ]);
    },
    getRowId: (row) => String(row.row),
    getRowCanExpand: () => false,
  });

  const counts = computed(() =>
    props.review.created === null
      ? null
      : props.review.skipped
        ? t(
            '{created, number} will be created, {updated, number} updated and {skipped, number} skipped.',
            {
              created: props.review.created,
              updated: props.review.updated ?? 0,
              skipped: props.review.skipped,
            }
          )
        : t(
            '{created, number} will be created and {updated, number} updated.',
            {
              created: props.review.created,
              updated: props.review.updated ?? 0,
            }
          )
  );

  // kept out of the template, whose interpolation would end at the message's `}}`
  const shown = computed(() =>
    t(
      '{shown, plural, =1{Showing the first row} other{Showing the first # rows}} of {total, number}.',
      {shown: props.review.rows.length, total: props.review.total}
    )
  );
</script>

<template>
  <p v-if="review.error" class="error m-0" role="alert">
    {{ review.error }}
  </p>
  <p v-else-if="!review.total" class="import-step-review__quiet m-0">
    {{ t('There’s no data to import.') }}
  </p>
  <template v-else>
    <p v-if="counts" class="m-0">{{ counts }}</p>
    <div class="import-step-review__table">
      <AdminTable :table="table" :title="label" :show-footer="false" />
    </div>
    <p
      v-if="review.total > review.rows.length"
      class="import-step-review__quiet m-0"
    >
      {{ shown }}
    </p>
  </template>
</template>

<style scoped>
  .import-step-review__table {
    overflow-x: auto;
  }

  .import-step-review__table :deep(.import-step-review__update) {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
  }

  .import-step-review__quiet {
    color: var(--c-text-quiet);
  }
</style>
