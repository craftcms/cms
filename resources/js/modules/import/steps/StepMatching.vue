<script setup lang="ts">
  /**
   * What a step does with rows that already exist: whether it creates new elements, updates
   * the ones its rows match, or both, and which mapped fields it matches them by.
   */
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/field/field';
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type {
    ImportStep,
    MappingColumn,
    MappingColumnEntry,
  } from '@/modules/import/mapping/types';
  import {
    checkedValue,
    getAt,
    isChecked,
    isCompoundMappingColumn,
    setAt,
  } from '@/modules/import/mapping/paths';

  const props = defineProps<{
    cols: MappingColumnEntry[];
    step: ImportStep;
    /** What the step imports, in the plural and lowercase, e.g. “entries”. */
    targetName: string | null;
  }>();

  const emit = defineEmits<{
    change: [
      settings: {matchMode: string; matchCriteria: Record<string, unknown>},
    ];
  }>();

  const settings = computed<Record<string, any>>(() =>
    Array.isArray(props.step.settings) ? {} : (props.step.settings ?? {})
  );

  const mode = computed(
    () => (settings.value.matchMode as string | undefined) ?? 'createAndUpdate'
  );
  // PHP sends an empty tree as `[]`, which a column's path can't be written into
  const criteria = computed<Record<string, unknown>>(() => {
    const tree = settings.value.matchCriteria;

    return tree && typeof tree === 'object' && !Array.isArray(tree) ? tree : {};
  });

  const modes = computed(() => [
    {
      value: 'createOnly',
      label: t('Only create new ones'),
      instructions: t('Rows that match something already there are skipped.'),
    },
    {
      value: 'createAndUpdate',
      label: t('Create new ones and update existing ones'),
      instructions: t('The usual choice for keeping content in sync.'),
    },
    {
      value: 'updateOnly',
      label: t('Only update existing ones'),
      instructions: t('Rows that don’t match anything are skipped.'),
    },
  ]);

  /** The mapped columns a row can be matched by, with any compound column's heading. */
  const matchable = computed(() => {
    const out: Array<{col: MappingColumn; label: string}> = [];
    const map = settings.value.map ?? {};

    for (const entry of props.cols) {
      const cols = isCompoundMappingColumn(entry) ? entry.subfields : [entry];
      const heading = isCompoundMappingColumn(entry) ? entry.heading : null;

      for (const col of cols) {
        if (
          !col.isContainer &&
          col.canBeMatchCriteria &&
          getAt(map, col.prefixedHandleAsArray)
        ) {
          out.push({
            col,
            label: heading ? `${heading} › ${col.label}` : col.label,
          });
        }
      }
    }

    return out;
  });

  function setMode(value: string): void {
    emit('change', {matchMode: value, matchCriteria: criteria.value});
  }

  function setCriterion(col: MappingColumn, event: Event): void {
    const next = JSON.parse(JSON.stringify(criteria.value));
    setAt(next, col.prefixedHandleAsArray, checkedValue(event));
    emit('change', {matchMode: mode.value, matchCriteria: next});
  }
</script>

<template>
  <div class="step-matching">
    <craft-field fieldset>
      <span slot="label">{{ t('Match against') }}</span>
      <div slot="input" class="step-matching__criteria">
        <craft-checkbox
          v-for="{col, label} in matchable"
          :key="col.prefixedHandle"
          .checked="isChecked(getAt(criteria, col.prefixedHandleAsArray))"
          @model-value-changed="setCriterion(col, $event)"
          ><label slot="label">{{ label }}</label></craft-checkbox
        >
        <p v-if="!matchable.length" class="step-matching__quiet m-0">
          {{
            t(
              'Map a field that can be matched on, such as a slug or an email, to match rows by it.'
            )
          }}
        </p>
        <p
          v-else-if="
            mode !== 'createAndUpdate' &&
            !matchable.some(({col}) =>
              isChecked(getAt(criteria, col.prefixedHandleAsArray))
            )
          "
          class="step-matching__quiet m-0"
        >
          {{ t('Choose a field to match by, or every row will count as new.') }}
        </p>
      </div>
    </craft-field>

    <craft-field fieldset>
      <span slot="label">{{ t('What if a row already exists?') }}</span>
      <div slot="input" class="step-matching__modes">
        <label
          v-for="option in modes"
          :key="option.value"
          class="step-matching__mode"
        >
          <input
            type="radio"
            :name="`match-mode-${step.uid}`"
            :value="option.value"
            :checked="mode === option.value"
            @change="setMode(option.value)"
          />
          <span class="step-matching__mode-text">
            <span class="step-matching__mode-label">{{ option.label }}</span>
            <span class="step-matching__mode-instructions">{{
              option.instructions
            }}</span>
          </span>
        </label>
      </div>
    </craft-field>
  </div>
</template>

<style scoped>
  .step-matching {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xl);
  }

  .step-matching__modes {
    display: grid;
    gap: var(--c-spacing-sm);
    max-inline-size: 48rem;
  }

  .step-matching__mode {
    display: flex;
    align-items: flex-start;
    gap: var(--c-spacing-md);
    cursor: pointer;
  }

  .step-matching__mode input {
    margin-block-start: 0.2em;
  }

  .step-matching__mode-text {
    display: grid;
    gap: var(--c-spacing-xs);
  }

  .step-matching__mode-label {
    font-weight: 600;
  }

  .step-matching__mode-instructions,
  .step-matching__quiet {
    color: var(--c-text-quiet);
  }

  .step-matching__criteria {
    display: grid;
    gap: var(--c-spacing-sm);
  }
</style>
