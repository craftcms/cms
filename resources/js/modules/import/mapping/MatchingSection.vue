<script setup lang="ts">
  /**
   * What a step does with the rows that match an existing element. The fields rows are
   * matched by are chosen in the mapping table's Mode column.
   */
  import '@craftcms/ui/components/field/field';
  import {t} from '@craftcms/ui';
  import {computed, useId, watch} from 'vue';
  import {
    DEFAULT_MATCH_MODE,
    isMatchKey,
    matchModes,
    setMatchMode,
  } from './matching';
  import {getAt, hasMapping, isCompoundMappingColumn} from './paths';
  import {useMappingContext, type MappingColumnEntry} from './types';

  const {cols} = defineProps<{cols: MappingColumnEntry[]}>();

  const context = useMappingContext();
  const radioName = useId();

  const hasCriteria = computed(() =>
    cols
      .flatMap((entry) =>
        isCompoundMappingColumn(entry) ? entry.subfields : [entry]
      )
      .some(
        (col) =>
          hasMapping(getAt(context.values.map, col.prefixedHandleAsArray)) &&
          isMatchKey(context.values, col)
      )
  );

  /** Without anything to match against, every row is new, so all a step can do is create. */
  const shownMode = computed(() =>
    hasCriteria.value
      ? (context.values.matchMode ?? DEFAULT_MATCH_MODE)
      : 'createOnly'
  );

  /**
   * Nothing to match against means nothing can be updated, however a match field stopped
   * being one, so the step falls back to creating, and starts matching again from the usual choice.
   */
  watch(hasCriteria, (matching) => {
    if (context.values.matchMode !== null) {
      setMatchMode(
        context.values,
        matching ? DEFAULT_MATCH_MODE : 'createOnly'
      );
    }
  });
</script>

<template>
  <craft-field
    v-if="context.values.matchMode !== null"
    class="matching-section"
    fieldset
  >
    <span slot="label">{{ t('What to do when matched') }}</span>
    <div slot="input" class="matching-section__modes">
      <label
        v-for="option in matchModes()"
        :key="option.value"
        class="matching-section__mode"
      >
        <input
          type="radio"
          :name="radioName"
          :value="option.value"
          :checked="shownMode === option.value"
          :disabled="!context.editable || !hasCriteria || undefined"
          @change="setMatchMode(context.values, option.value)"
        />
        <span class="matching-section__mode-text">
          <span class="matching-section__mode-label">{{ option.label }}</span>
          <span class="matching-section__quiet">{{ option.instructions }}</span>
        </span>
      </label>
    </div>
    <span v-if="!hasCriteria" slot="warning">{{
      t('Set a field’s mode to Match to update existing elements.')
    }}</span>
  </craft-field>
</template>

<style scoped>
  .matching-section__modes {
    display: grid;
    gap: var(--c-spacing-sm);
    max-inline-size: 48rem;
  }

  .matching-section__mode {
    display: flex;
    align-items: flex-start;
    gap: var(--c-spacing-md);
    cursor: pointer;
  }

  .matching-section__mode input {
    margin-block-start: 0.2em;
  }

  .matching-section__mode-text {
    display: grid;
    gap: var(--c-spacing-xs);
  }

  .matching-section__mode-label {
    font-weight: 600;
  }

  .matching-section__quiet {
    color: var(--c-text-quiet);
  }

  .matching-section__mode:has(input:disabled) {
    color: var(--c-text-quiet);
    cursor: not-allowed;
  }
</style>
