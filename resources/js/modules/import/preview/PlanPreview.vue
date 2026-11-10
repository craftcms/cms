<script setup lang="ts">
  /**
   * What running the plan would do to each of a step's first items, from the
   * step as the form has it, with a button to see how each item's element
   * would change.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/spinner/spinner';
  import {Color, isHttpError, t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import Select from '@/common/form/Select.vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import ItemPreviewModal from './ItemPreviewModal.vue';
  import {
    fetchStepPreview,
    resultFills,
    resultLabels,
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

  /** The steps that run, with what they're called in the step menu, e.g. “1. Entries — Guides”. */
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
          label: `${i + 1}. ${[type, ...settings].join(' — ')}`,
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
  /** Which items the table lists, by their result. */
  const filter = shallowRef<PreviewResult | 'all'>('all');
  const loading = shallowRef(false);
  let request = 0;

  async function load(step: ImportStep): Promise<void> {
    const current = ++request;
    loading.value = true;

    try {
      const data = await fetchStepPreview(step);

      if (current === request) {
        preview.value = data;
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

  // reloads as the step is edited, but not when the list is merely re-rendered
  watch(
    () => (selected.value ? JSON.stringify(selected.value.step) : null),
    () => {
      filter.value = 'all';

      if (selected.value) {
        load(selected.value.step);
      } else {
        preview.value = null;
      }
    },
    {immediate: true}
  );

  const items = computed(() => preview.value?.items ?? []);
  const matchColumns = computed(() => preview.value?.matchColumns ?? []);

  const filters = computed(() => {
    const counts = new Map<PreviewResult, number>();
    items.value.forEach((item) =>
      counts.set(item.result, (counts.get(item.result) ?? 0) + 1)
    );

    return [
      {value: 'all' as const, label: t('All'), count: items.value.length},
      ...(Object.keys(resultLabels) as PreviewResult[])
        .filter((result) => counts.has(result))
        .map((result) => ({
          value: result,
          label: resultLabels[result],
          count: counts.get(result)!,
        })),
    ];
  });

  const rows = computed(() =>
    filter.value === 'all'
      ? items.value
      : items.value.filter((item) => item.result === filter.value)
  );

  /** The previewed item's position among the rows, while the modal shows it. */
  const previewing = shallowRef<number | null>(null);
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
      <div class="plan-preview__bar">
        <Select
          v-if="stepOptions.length > 1"
          small
          :label="t('Step')"
          label-sr-only
          :options="stepOptions"
          :model-value="selected?.value ?? ''"
          @update:model-value="selectedUid = String($event)"
        />
        <craft-button-group
          v-if="items.length"
          role="group"
          :aria-label="t('Show items')"
        >
          <craft-button
            v-for="option in filters"
            :key="option.value"
            class="plan-preview__filter"
            size="small"
            :active="filter === option.value"
            :aria-pressed="filter === option.value ? 'true' : 'false'"
            @click="filter = option.value"
          >
            {{ option.label }}
            <span class="plan-preview__count">{{ option.count }}</span>
          </craft-button>
        </craft-button-group>
        <craft-spinner
          v-if="loading"
          visible
          size="small"
          :label="t('Loading')"
        ></craft-spinner>
      </div>

      <p v-if="preview?.error" class="m-0 error" role="alert">
        {{ preview.error }}
      </p>
      <p v-else-if="preview && !items.length" class="m-0 text-quiet">
        {{ t('This step’s data has no items.') }}
      </p>
      <div v-else-if="items.length" class="plan-preview__scroll">
        <table
          class="plan-preview__table cp-table cp-table--auto cp-table--aligned"
          :aria-busy="loading || undefined"
        >
          <thead>
            <tr>
              <th scope="col">{{ t('Title') }}</th>
              <th v-for="column in matchColumns" :key="column.key" scope="col">
                <span class="plan-preview__heading">
                  {{ column.label }}
                  <craft-badge size="small" :fill="Color.Accent" no-prefix>{{
                    t('Match key')
                  }}</craft-badge>
                </span>
              </th>
              <th scope="col">{{ t('Result') }}</th>
              <th scope="col">
                <span class="sr-only">{{ t('Actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, position) in rows" :key="item.index">
              <td>
                <span v-if="item.title">{{ item.title }}</span>
                <span v-else class="text-quiet">{{ t('Untitled') }}</span>
              </td>
              <td v-for="column in matchColumns" :key="column.key">
                <code v-if="item.match[column.key]" class="text-sm">{{
                  item.match[column.key]
                }}</code>
              </td>
              <td>
                <craft-badge
                  size="small"
                  :fill="resultFills[item.result]"
                  no-prefix
                  >{{ resultLabels[item.result] }}</craft-badge
                >
              </td>
              <td class="plan-preview__actions">
                <craft-button
                  type="button"
                  size="small"
                  :aria-label="
                    t('Preview {item}', {item: item.title || t('Untitled')})
                  "
                  @click="previewing = position"
                  >{{ t('Preview') }}</craft-button
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p
        v-if="preview?.total && preview.total > items.length"
        class="m-0 text-sm text-quiet"
      >
        {{
          t('Showing the first {count, number} of {total, number} items.', {
            count: items.length,
            total: preview.total,
          })
        }}
      </p>
    </div>
  </craft-field>
  <ItemPreviewModal
    v-if="selected"
    v-model="previewing"
    :step="selected.step"
    :items="rows"
  />
</template>

<style scoped>
  .plan-preview__body {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
  }

  .plan-preview__bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  /* room for the buttons' focus rings, which the scrolling would clip, without moving the table */
  .plan-preview__scroll {
    --_ring: calc(var(--c-focus-outline-width) + var(--c-focus-outline-offset));
    overflow-x: auto;
    padding: var(--_ring);
    margin: calc(var(--_ring) * -1);
  }

  .plan-preview__filter {
    padding-inline: var(--c-spacing-md);
  }

  .plan-preview__count {
    margin-inline-start: var(--c-spacing-xs);
    opacity: 0.7;
  }

  .plan-preview__heading {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .plan-preview__actions {
    text-align: end;
  }
</style>
