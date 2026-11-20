<script setup lang="ts">
  /**
   * What running the plan would do to each of a step's items, a page at a
   * time, from the step as the form has it, with a button to see how each
   * item's element would change.
   */
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/tab/tab';
  import '@craftcms/ui/components/tabs/tabs';
  import {isHttpError, t} from '@craftcms/ui';
  import {computed, shallowReactive, shallowRef, watch} from 'vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import ItemPreviewModal from './ItemPreviewModal.vue';
  import ItemResultsTable from './ItemResultsTable.vue';
  import StepNumber from '@/modules/import/steps/StepNumber.vue';
  import {stepDestination} from '@/modules/import/steps/step-settings';
  import {
    fetchStepPreview,
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

  /** The steps that run, with their number in the plan and what their tab calls them, e.g. “Entries → Guides › Guide”, as the list of plans does. */
  const stepOptions = computed(() =>
    steps.flatMap((step, i) => {
      if (!step.enabled || !step.type) {
        return [];
      }

      const type =
        importerTypes.find((option) => option.value === step.type)?.label ??
        step.type;
      const destination = stepDestination(
        stepSummaries[step.uid]?.settings ?? []
      );

      return [
        {
          value: step.uid,
          number: i + 1,
          type,
          destination,
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

  function previewItem(item: PreviewItem): void {
    previewing.value = previewable.value.indexOf(item);
  }
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
          :has-errors="problemSteps.has(option.value) || undefined"
        >
          <StepNumber slot="prefix" size="small" :number="option.number" />
          <span class="plan-preview__step-type">{{ option.type }}</span>
          <span v-if="option.destination" class="text-quiet">
            → {{ option.destination }}</span
          >
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
            <ItemResultsTable
              :items="items"
              :row-id="(item) => String(item.index)"
              :match-columns="matchColumns"
              :counts="preview?.counts ?? null"
              :total="preview?.total ?? 0"
              :matching="matching"
              :page="page"
              :page-size="pageSize"
              :loading="loading"
              :placeholder-rows="placeholderRows"
              :actions="
                (item) =>
                  previewable.includes(item)
                    ? [
                        {
                          icon: 'eye',
                          tooltip: t('Preview'),
                          label: t('Preview {item}', {
                            item: item.title || t('Untitled'),
                          }),
                          onClick: () => previewItem(item),
                        },
                      ]
                    : []
              "
              :filter="filter"
              @update:filter="showFilter"
              @page="showPage"
            >
              <template #notice>
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
              </template>
            </ItemResultsTable>
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

  .plan-preview__step {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  .plan-preview__step-type {
    font-weight: var(--font-weight-semibold);
  }

  craft-tab[selected] {
    --step-number-color: var(--c-color-accent-on-quiet);
  }
</style>
