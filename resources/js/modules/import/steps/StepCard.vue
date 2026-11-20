<script setup lang="ts">
  /**
   * One step in an import's list: what it imports and from where, its settings at a
   * glance, and its mapping, with buttons to edit each.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import type {StepSummary} from './step-slideout';
  import StepCardLayout from './StepCardLayout.vue';
  import StepMappingSummary from './StepMappingSummary.vue';
  import {stepSettingRows} from './step-settings';

  const {
    step,
    number,
    typeLabel,
    summary,
    editable,
    canToggle,
    invalid,
    opening,
    over,
  } = defineProps<{
    step: ImportStep;
    /** The step's place in the list, from 1. */
    number: number;
    typeLabel: string;
    summary: StepSummary | null;
    editable: boolean;
    /** Whether the step can be disabled or enabled; the last enabled one can't be. */
    canToggle: boolean;
    invalid: boolean;
    /** Which of the step's slideouts is opening: its settings, its mapping, or a container's mapping by its key. */
    opening: 'settings' | 'mapping' | {nested: string} | null;
    /** Whether a dragged step is over this one. */
    over: boolean;
  }>();

  const emit = defineEmits<{
    edit: [opener: HTMLElement | null];
    editMapping: [opener: HTMLElement | null];
    editNestedMapping: [key: string, opener: HTMLElement];
    toggleEnabled: [];
    remove: [];
  }>();

  const settingsButton = useTemplateRef<HTMLElement>('settingsButton');
  const mappingButton = useTemplateRef<HTMLElement>('mappingButton');

  const labelId = computed(() => `step-label-${step.uid}`);
  const errorId = computed(() => `step-error-${step.uid}`);

  /** What the step imports and from where, e.g. “Entries — people.csv”. */
  const description = computed(() =>
    [typeLabel, step.source].filter(Boolean).join(' — ')
  );

  /** Just the source's file name; its whole path is in its tooltip. */
  const sourceName = computed(() => step.source?.split('/').pop() ?? '');

  /** The step as it's named to screen reader users, e.g. “1. Entries — people.csv”. */
  const name = computed(() => `${number}. ${description.value}`);

  const settingsRows = computed(() => stepSettingRows(step, summary));

  const actions = computed<ActionItems>(() => [
    {
      label: step.enabled ? t('Disable step') : t('Enable step'),
      icon: step.enabled ? 'circle-dashed' : 'circle',
      disabled: !canToggle,
      onClick: () => emit('toggleEnabled'),
    },
    {
      label: t('Delete step'),
      icon: 'trash',
      variant: 'danger',
      onClick: () => emit('remove'),
    },
  ]);
</script>

<template>
  <li
    class="step-card"
    :class="{
      'step-card--over': over,
      'opacity-60': !step.enabled,
    }"
    :aria-labelledby="labelId"
  >
    <StepCardLayout
      :number="number"
      :label="typeLabel"
      :source="sourceName"
      :source-title="step.source"
      :settings="settingsRows"
      :label-id="labelId"
    >
      <template #actions>
        <ActionMenu
          v-if="editable"
          :label="t('Actions for {item}', {item: name})"
          :actions="actions"
        />
        <slot name="handle"></slot>
      </template>
      <template #label-badge>
        <craft-badge v-if="!step.enabled" size="small" no-prefix>{{
          t('Disabled')
        }}</craft-badge>
      </template>
      <template #notice>
        <p v-if="invalid" :id="errorId" class="error m-0">
          {{
            t('The step “{step}” is invalid. Edit it to fix the problem.', {
              step: description,
            })
          }}
        </p>
      </template>
      <!-- read-only, the rail already shows every setting -->
      <template v-if="editable" #footer>
        <craft-button
          ref="settingsButton"
          size="small"
          flush
          variant="plain"
          icon="pencil"
          .loading="opening === 'settings'"
          :aria-describedby="invalid ? errorId : undefined"
          :aria-label="t('Edit {type}', {type: name})"
          @click="emit('edit', settingsButton)"
        >
          {{ t('Edit settings') }}
        </craft-button>
      </template>

      <div class="step-card__mapping">
        <StepMappingSummary
          :step="step"
          :labels="summary?.destinationLabels ?? {}"
          :editable="editable"
          :opening-nested="
            typeof opening === 'object' && opening ? opening.nested : null
          "
          @edit-nested="(key, opener) => emit('editNestedMapping', key, opener)"
        >
          <template #header="{count}">
            <div class="flex items-center justify-between gap-sm">
              <h3 class="m-0 text-base">{{ t('Mapping') }}</h3>
              <craft-button
                v-if="count"
                ref="mappingButton"
                size="small"
                variant="plain"
                :icon="editable ? 'pencil' : 'eye'"
                .loading="opening === 'mapping'"
                :aria-label="
                  editable
                    ? t('Edit mapping for {type}', {type: name})
                    : t('View mapping for {type}', {type: name})
                "
                @click="emit('editMapping', mappingButton)"
              >
                {{ editable ? t('Edit mapping') : t('View mapping') }}
              </craft-button>
            </div>
          </template>
          <!-- like a nested element field's add button -->
          <template v-if="editable" #empty>
            <div>
              <craft-button
                ref="mappingButton"
                type="button"
                variant="dashed"
                icon="plus"
                .loading="opening === 'mapping'"
                :aria-label="t('Add mapping for {type}', {type: name})"
                @click="emit('editMapping', mappingButton)"
              >
                {{ t('Add mapping') }}
              </craft-button>
            </div>
          </template>
        </StepMappingSummary>
      </div>
    </StepCardLayout>
  </li>
</template>

<style scoped>
  .step-card--over .step-card__card {
    border-color: var(--c-color-accent-border-normal);
  }

  .step-card__mapping {
    min-width: 0;
  }

  /* the mapping table runs edge to edge of the card, its first and last columns lined up with the heading */
  .step-card__mapping :deep(.step-mapping-summary__scroll) {
    margin-inline: calc(var(--c-spacing-lg) * -1);
  }

  /* and its footer's rule with it */
  .step-card__mapping :deep(.step-mapping-summary__footer) {
    margin-inline: calc(var(--c-spacing-lg) * -1);
    padding-inline: var(--c-spacing-lg);
  }

  .step-card__mapping :deep(.step-mapping-summary__table) {
    --mapping-summary-inset: var(--c-spacing-lg);
  }

  .step-card__mapping :deep(.step-mapping-summary__table thead th:first-child) {
    padding-inline-start: var(--mapping-summary-inset);
  }

  .step-card__mapping
    :deep(.step-mapping-summary__table :is(th, td):last-child) {
    padding-inline-end: var(--c-spacing-lg);
  }
</style>
