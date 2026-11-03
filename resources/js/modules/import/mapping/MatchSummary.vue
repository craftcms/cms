<script setup lang="ts">
  /**
   * What a step matches existing elements on, as set by its own columns' Match
   * checkboxes, with a test of it: the first elements its rows would update.
   *
   * An element has to match on every one of the columns, as they're compared at once.
   * A container's own columns match its nested elements instead, so they're left out.
   */
  import '@craftcms/ui/components/button/button';
  import {computed, h, inject, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {useCraftTable} from '@/common/table/craftTable';
  import {getAt, isChecked, isCompoundMappingColumn} from './paths';
  import {
    testMatch,
    type MatchResult,
    type TestMatchResults,
  } from './test-match';
  import {
    MappingContextKey,
    type ImportStep,
    type MappingColumn,
    type MappingColumnEntry,
  } from './types';

  const props = defineProps<{
    cols: MappingColumnEntry[];
    /** The draft step being mapped, posted to test its matching. */
    step: ImportStep;
    /** What the step imports, in the plural and lowercase, e.g. “entries”. */
    targetName: string | null;
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error('MatchSummary must be rendered inside a mapping editor.');
  }

  const type = computed(() => props.targetName ?? t('elements'));

  const matched = computed(() =>
    props.cols.filter(
      (col): col is MappingColumn =>
        !isCompoundMappingColumn(col) &&
        !col.isContainer &&
        isChecked(
          getAt(context.values.matchCriteria, col.prefixedHandleAsArray)
        )
    )
  );

  const summary = computed(() =>
    matched.value.length
      ? t('The import will match existing {type} by {fields}.', {
          type: type.value,
          fields: new Intl.ListFormat(
            document.documentElement.lang || undefined,
            {type: 'conjunction'}
          ).format(matched.value.map((col) => col.label)),
        })
      : null
  );

  const testing = ref(false);
  const results = ref<TestMatchResults | null>(null);
  const testError = ref<string | null>(null);

  // a test is about the matching it ran with, so a change to it sets the test aside
  watch(
    () =>
      JSON.stringify(
        matched.value.map((col) => [
          col.prefixedHandle,
          getAt(context.values.map, col.prefixedHandleAsArray),
          getAt(context.values.templates, col.prefixedHandleAsArray),
        ])
      ),
    () => {
      results.value = null;
      testError.value = null;
    }
  );

  async function runTest(): Promise<void> {
    testing.value = true;
    testError.value = null;

    try {
      results.value = await testMatch(
        props.step,
        JSON.parse(JSON.stringify(context!.values))
      );
    } catch (error: any) {
      results.value = null;
      testError.value =
        error?.response?.data?.message ??
        (error instanceof Error && error.message
          ? error.message
          : t('Couldn’t test the matching.'));
    } finally {
      testing.value = false;
    }
  }

  // kept out of the template, whose interpolation would end at the message's `}}`
  const noMatches = computed(() =>
    results.value && !results.value.matches.length
      ? t(
          '{total, plural, =1{The row doesn’t match} other{None of the # rows match}} any existing {type}.',
          {total: results.value.total, type: type.value}
        )
      : null
  );

  const resultHelper = createCraftColumnHelper<MatchResult>();

  const resultsTable = useCraftTable({
    get data() {
      return results.value?.matches ?? [];
    },
    columns: resultHelper.columns([
      resultHelper.display({
        id: 'element',
        header: () => t('Existing {type}', {type: type.value}),
        meta: {cellTag: 'th', wrap: true},
        cell: ({row}) =>
          row.original.url
            ? h(
                'a',
                {href: row.original.url, target: '_blank', rel: 'noopener'},
                row.original.label
              )
            : row.original.label,
      }),
      resultHelper.display({
        id: 'row',
        header: t('Matched by row'),
        meta: {columnClass: 'match-summary__col-row'},
        cell: ({row}) => String(row.original.row),
      }),
    ]),
    getRowId: (row) => `${row.row}-${row.id}`,
    getRowCanExpand: () => false,
  });
</script>

<template>
  <div v-if="summary" class="match-summary">
    <p class="match-summary__text m-0">{{ summary }}</p>
    <craft-button
      type="button"
      size="small"
      class="match-summary__test-button"
      .loading="testing"
      @click="runTest"
    >
      {{ t('Test match') }}
    </craft-button>

    <div aria-live="polite" class="match-summary__results">
      <p v-if="testError" class="error m-0">{{ testError }}</p>
      <template v-else-if="results">
        <p v-if="noMatches" class="m-0">{{ noMatches }}</p>
        <div v-else class="match-summary__table">
          <AdminTable
            :table="resultsTable"
            :title="t('Test match')"
            :show-footer="false"
          />
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
  .match-summary {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--c-spacing-sm);
    margin-block-start: var(--c-spacing-lg);
  }

  .match-summary__results {
    align-self: stretch;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .match-summary__results:empty {
    display: none;
  }

  .match-summary__table {
    overflow-x: auto;
  }

  .match-summary__table :deep(.match-summary__col-row) {
    width: 1%;
    white-space: nowrap;
  }
</style>
