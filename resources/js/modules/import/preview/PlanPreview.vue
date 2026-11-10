<script setup lang="ts">
  /**
   * What running the plan would do to each of a step's items, a page at a
   * time, from the step as the form has it, with a button to see how each
   * item's element would change.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/tab/tab';
  import '@craftcms/ui/components/tabs/tabs';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {Color, isHttpError, t} from '@craftcms/ui';
  import {computed, h, shallowReactive, shallowRef, watch} from 'vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import ItemPreviewModal from './ItemPreviewModal.vue';
  import {
    fetchStepPreview,
    resultFills,
    resultLabels,
    type PreviewItem,
    type PreviewResult,
    type StepPreview,
  } from './plan-preview';

  type StepSummary = CraftCms.Cms.Import.Data.StepSummary;

  const {
    steps,
    importerTypes,
    stepSummaries = {},
  } = defineProps<{
    steps: ImportStep[];
    importerTypes: Array<{value: string; label: string}>;
    stepSummaries?: Record<string, StepSummary>;
  }>();

  /** The steps that run, with their number in the plan and what their tab calls them, e.g. “Entries — Guides”. */
  const stepOptions = computed(() =>
    steps.flatMap((step, i) => {
      if (!step.enabled || !step.type) {
        return [];
      }

      const type =
        importerTypes.find((option) => option.value === step.type)?.label ??
        step.type;
      const settings = (stepSummaries[step.uid]?.settings ?? []).map(
        (setting) => setting.value
      );

      return [
        {
          value: step.uid,
          number: i + 1,
          name: [type, ...settings].join(' — '),
          step,
        },
      ];
    })
  );

  const selectedUid = shallowRef<string | null>(null);
  const selected = computed(
    () =>
      stepOptions.value.find((option) => option.value === selectedUid.value) ??
      stepOptions.value[0] ??
      null
  );

  const preview = shallowRef<StepPreview | null>(null);
  /** The previewed item's position among the page's previewable items, while the modal shows it. */
  const previewing = shallowRef<number | null>(null);
  /** Which items the table pages through, by their result. */
  const filter = shallowRef<PreviewResult | 'all'>('all');
  const loading = shallowRef(false);
  let request = 0;
  /** The steps whose previews found errors, as far as they've been previewed. */
  const problemSteps = shallowReactive(new Set<string>());
  /** The step the preview is of, which keeps its page as it's edited. */
  let shownUid: string | null = null;

  /** How many rows stand in for the page while it loads: as many as it will have, as far as that's known. */
  const placeholderRows = shallowRef(10);

  /**
   * Loads a page of the step's preview: the one asked for, or, given
   * `previewableIndex`, the one with that item among those with something to
   * compare, which the modal then shows.
   */
  async function load(
    step: ImportStep,
    page = 0,
    previewableIndex: number | null = null
  ): Promise<void> {
    const current = ++request;
    const size = preview.value?.pageSize ?? 10;
    const known =
      filter.value === 'all'
        ? preview.value?.total
        : preview.value?.counts?.[filter.value];
    placeholderRows.value =
      known === undefined
        ? size
        : Math.max(1, Math.min(size, known - page * size));
    loading.value = true;

    try {
      const result = filter.value === 'all' ? null : filter.value;
      const data = await fetchStepPreview(step, page, result, previewableIndex);

      if (current !== request) {
        return;
      }

      // an edit to the step can leave nothing with the chosen result
      if (result && !data.counts?.[result]) {
        filter.value = 'all';
        void load(step);

        return;
      }

      preview.value = data;

      if (previewableIndex !== null) {
        const shown = previewableIndex - (data.previewableBefore ?? 0);
        previewing.value =
          shown >= 0 && shown < previewable.value.length ? shown : null;
      }

      if (data.counts?.problem) {
        problemSteps.add(step.uid);
      } else {
        problemSteps.delete(step.uid);
      }
    } catch (error) {
      if (current === request) {
        preview.value = {
          error:
            isHttpError(error) && error.message
              ? error.message
              : t('The step couldn’t be previewed.'),
        };
      }
    } finally {
      if (current === request) {
        loading.value = false;
      }
    }
  }

  const items = computed(() => preview.value?.items ?? []);
  const page = computed(() => preview.value?.page ?? 0);
  const pageSize = computed(() => preview.value?.pageSize ?? 10);
  /** How many items have the chosen result, which are paged through. */
  const matching = computed(() =>
    filter.value === 'all'
      ? (preview.value?.total ?? 0)
      : (preview.value?.counts?.[filter.value] ?? 0)
  );
  const pageCount = computed(() => Math.ceil(matching.value / pageSize.value));

  function showPage(index: number): void {
    if (selected.value) {
      load(selected.value.step, index);
    }
  }

  /** Moves the modal to an item on another page. */
  function previewOnPage(previewableIndex: number): void {
    if (selected.value) {
      load(selected.value.step, page.value, previewableIndex);
    }
  }

  // reloads as the step is edited, staying on its page, but not when the list is merely re-rendered
  watch(
    () => (selected.value ? JSON.stringify(selected.value.step) : null),
    () => {
      if (selected.value) {
        const sameStep = selected.value.value === shownUid;

        // another step's preview, and its messages, don't stand in for this one's while it loads
        if (!sameStep) {
          preview.value = null;
          previewing.value = null;
        }

        load(selected.value.step, sameStep ? page.value : 0);
        shownUid = selected.value.value;
      } else {
        preview.value = null;
        shownUid = null;
      }
    },
    {immediate: true}
  );

  const matchColumns = computed(() => preview.value?.matchColumns ?? []);
  /** Each match column's share of the table, which together leave the title at least as much as they take. */
  const matchColumnWidth = computed(
    () => `${Math.min(35, 50 / Math.max(1, matchColumns.value.length))}%`
  );

  const filters = computed(() =>
    (['all', ...Object.keys(resultLabels)] as Array<PreviewResult | 'all'>).map(
      (value) => {
        const count =
          value === 'all'
            ? (preview.value?.total ?? 0)
            : (preview.value?.counts?.[value] ?? 0);

        return {
          value,
          count,
          label: value === 'all' ? t('All') : resultLabels[value],
        };
      }
    )
  );

  const countFormat = new Intl.NumberFormat(
    document.documentElement.lang || undefined
  );

  function showFilter(value: PreviewResult | 'all'): void {
    if (value !== filter.value) {
      filter.value = value;

      if (selected.value) {
        load(selected.value.step);
      }
    }
  }

  const stepIndex = computed(() =>
    Math.max(
      0,
      stepOptions.value.findIndex(
        (option) => option.value === selected.value?.value
      )
    )
  );

  function onStepShown(event: Event): void {
    const index = (event.target as {selectedIndex?: number}).selectedIndex;
    const option = index === undefined ? undefined : stepOptions.value[index];

    if (option) {
      selectedUid.value = option.value;
    }
  }

  function panelId(uid: string): string {
    return `plan-preview-step-${uid}`;
  }

  /** The page's items whose elements running the step would change, the only ones with something to compare. */
  const previewable = computed(() =>
    items.value.filter(
      (item) => item.result !== 'skip' && item.result !== 'unchanged'
    )
  );

  function previewButtonId(item: PreviewItem): string {
    return `plan-preview-${selected.value?.value}-${item.index}`;
  }

  const columnHelper = createCraftColumnHelper<PreviewItem>();

  const columns = computed(() =>
    columnHelper.columns([
      columnHelper.display({
        id: 'result',
        header: t('Result'),
        meta: {columnClass: 'plan-preview__result-col'},
        cell: ({row}) =>
          h(
            'craft-badge',
            {fill: resultFills[row.original.result], 'no-prefix': ''},
            resultLabels[row.original.result]
          ),
      }),
      columnHelper.display({
        id: 'title',
        header: t('Title'),
        meta: {columnClass: 'plan-preview__title-col'},
        cell: ({row}) =>
          row.original.title || h('span', {class: 'text-quiet'}, t('Untitled')),
      }),
      ...matchColumns.value.map((column) =>
        columnHelper.display({
          id: `match:${column.key}`,
          header: () =>
            h('span', {class: 'plan-preview__heading'}, [
              column.label,
              h(
                'craft-badge',
                {size: 'small', fill: Color.Accent, 'no-prefix': ''},
                t('Match key')
              ),
            ]),
          meta: {columnClass: 'plan-preview__match-col'},
          cell: ({row}) =>
            row.original.match[column.key]
              ? h('code', {class: 'text-sm'}, row.original.match[column.key])
              : null,
        })
      ),
      columnHelper.display({
        id: 'actions',
        header: () => h('span', {class: 'sr-only'}, t('Actions')),
        meta: {
          columnClass: 'plan-preview__actions-col',
          cellClass: 'plan-preview__actions',
        },
        cell: ({row}) =>
          previewable.value.includes(row.original)
            ? h('span', [
                h('craft-button', {
                  id: previewButtonId(row.original),
                  type: 'button',
                  icon: 'eye',
                  variant: 'plain',
                  size: 'small',
                  'aria-label': t('Preview {item}', {
                    item: row.original.title || t('Untitled'),
                  }),
                  onClick: () =>
                    (previewing.value = previewable.value.indexOf(
                      row.original
                    )),
                }),
                h(
                  'craft-tooltip',
                  {for: previewButtonId(row.original)},
                  t('Preview')
                ),
              ])
            : null,
      }),
    ])
  );

  // paged on the server, which the table's own pagination state follows
  const table = useCraftTable({
    get data() {
      return items.value;
    },
    getRowId: (row) => String(row.index),
    get columns() {
      return columns.value;
    },
    manualPagination: true,
    get rowCount() {
      return matching.value;
    },
    state: {
      get pagination() {
        return {pageIndex: page.value, pageSize: pageSize.value};
      },
    },
    onPaginationChange: (updater) => {
      const next =
        typeof updater === 'function'
          ? updater({pageIndex: page.value, pageSize: pageSize.value})
          : updater;
      showPage(next.pageIndex);
    },
  });
</script>

<template>
  <craft-field
    fieldset
    class="plan-preview"
    :help-text="
      t(
        'What running this plan would do, from each step’s data. Nothing is saved.'
      )
    "
  >
    <span slot="label">{{ t('Preview') }}</span>

    <p v-if="!stepOptions.length" slot="input" class="m-0 text-quiet">
      {{ t('Add a step to see what running this plan would do.') }}
    </p>
    <div v-else slot="input" class="plan-preview__body">
      <craft-tabs
        v-if="stepOptions.length > 1"
        :label="t('Steps')"
        sync-location-hash="false"
        :selected-index="stepIndex"
        @craft-tab-show="onStepShown"
      >
        <craft-tab
          v-for="option in stepOptions"
          :key="option.value"
          slot="tab"
          :controls="panelId(option.value)"
        >
          <span slot="prefix" class="plan-preview__step-number">{{
            option.number
          }}</span>
          {{ option.name }}
          <craft-icon
            v-if="problemSteps.has(option.value)"
            slot="suffix"
            name="triangle-exclamation"
            data-color="danger"
            :label="t('Has errors')"
          ></craft-icon>
        </craft-tab>
      </craft-tabs>
      <section
        v-for="option in stepOptions"
        :id="panelId(option.value)"
        :key="option.value"
        :class="{hidden: option.value !== selected?.value}"
      >
        <div v-if="option.value === selected?.value" class="plan-preview__step">
          <p v-if="preview?.error" class="m-0 error" role="alert">
            {{ preview.error }}
          </p>
          <p v-else-if="preview && !items.length" class="m-0 text-quiet">
            {{ t('This step’s data has no items.') }}
          </p>
          <template v-else-if="items.length || loading">
            <div
              role="group"
              :aria-label="t('Show items')"
              class="plan-preview__chips"
            >
              <button
                v-for="chip in filters"
                :key="chip.value"
                type="button"
                class="plan-preview__chip"
                :aria-pressed="filter === chip.value ? 'true' : 'false'"
                :disabled="chip.value !== 'all' && !chip.count"
                @click="showFilter(chip.value)"
              >
                <craft-badge
                  :fill="
                    chip.value === 'all' ? Color.Gray : resultFills[chip.value]
                  "
                  no-prefix
                  >{{ chip.label }}
                  <span
                    v-if="preview?.counts"
                    slot="suffix"
                    class="plan-preview__chip-count"
                    >{{ countFormat.format(chip.count) }}</span
                  ></craft-badge
                >
              </button>
            </div>
            <p
              v-if="
                preview?.itemCount !== undefined &&
                preview.total !== undefined &&
                preview.itemCount > preview.total
              "
              class="m-0 text-sm text-quiet"
            >
              {{
                t(
                  'Previewing the first {total, number} of {count, number} items.',
                  {total: preview.total, count: preview.itemCount}
                )
              }}
            </p>
            <AdminTable
              class="plan-preview__table"
              :style="{'--plan-preview-match-width': matchColumnWidth}"
              :table="table"
              :title="t('Items')"
              layout="fixed"
              spacing="compact"
              :loading="loading"
              :placeholder-rows="loading ? placeholderRows : 0"
              :from="page * pageSize + 1"
              :to="page * pageSize + items.length"
              :total="matching"
            />
          </template>
        </div>
      </section>
    </div>
  </craft-field>
  <ItemPreviewModal
    v-if="selected"
    v-model="previewing"
    :step="selected.step"
    :items="previewable"
    :offset="preview?.previewableBefore ?? 0"
    :total="preview?.previewable ?? previewable.length"
    @navigate="previewOnPage"
  />
</template>

<style scoped>
  .plan-preview__body {
    --c-tabs-gap: var(--c-spacing-sm);
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
    margin-block-start: var(--c-spacing-md);
  }

  /* fixed at every width, so the columns don't move as the filters and pages change what's in them */
  .plan-preview__table :deep(table) {
    table-layout: fixed;
    min-inline-size: 36rem;
  }

  .plan-preview__table :deep(.plan-preview__title-col) {
    width: auto;
  }

  .plan-preview__table :deep(.plan-preview__match-col) {
    width: var(--plan-preview-match-width);
  }

  /* as wide as the widest result's badge, and the preview button */
  .plan-preview__table :deep(.plan-preview__result-col) {
    width: 8rem;
  }

  .plan-preview__table :deep(.plan-preview__actions-col) {
    width: 3rem;
  }

  .plan-preview__step {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  .plan-preview__step-number {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-inline-size: 1.25rem;
    block-size: 1.25rem;
    border-radius: var(--c-radius-full);
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    font-variant-numeric: tabular-nums;
    color: var(--c-color-neutral-on-normal);
    background-color: var(--c-color-neutral-fill-normal);
  }

  craft-tab[selected] .plan-preview__step-number {
    color: var(--c-color-accent-on-loud);
    background-color: var(--c-color-accent-fill-loud);
  }

  .plan-preview__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
  }

  /* the same badge the table shows each result in, as a button */
  .plan-preview__chip {
    display: inline-flex;
    padding: 0;
    font: inherit;
    background: none;
    border: 0;
    border-radius: var(--c-radius-full);
    cursor: pointer;
  }

  .plan-preview__chip:hover:not(:disabled, [aria-pressed='true'])
    craft-badge::part(badge) {
    border-color: var(--c-color-border-loud);
  }

  .plan-preview__chip[aria-pressed='true'] craft-badge::part(badge) {
    color: var(--c-color-on-loud);
    background-color: var(--c-color-fill-loud);
    border-color: transparent;
  }

  .plan-preview__chip:disabled {
    cursor: default;
    opacity: 0.5;
  }

  .plan-preview__chip-count {
    font-variant-numeric: tabular-nums;
    opacity: 0.75;
  }

  .plan-preview__table :deep(.plan-preview__heading) {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  /* cells clip their text, which would clip the button's focus ring along the table's edge */
  .plan-preview__table :deep(.plan-preview__actions) {
    text-align: end;
    overflow: visible;
  }
</style>
