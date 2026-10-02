<script setup lang="ts">
  /**
   * One destination column: what it maps to, and how it behaves when the imported
   * value is missing.
   *
   * A container column has no mapping of its own — it opens a nested panel over the
   * same trees instead, which is why every control here writes through
   * `prefixedHandleAsArray` rather than a form input name.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/info-icon/info-icon';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import {computed, inject, useTemplateRef} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/forms/runtime';
  import {checkedValue, getAt, isChecked, setAt} from './paths';
  import {type MappingCol, MappingContextKey} from './types';

  const props = defineProps<{
    col: MappingCol;
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error('MappingRow must be rendered inside a mapping screen.');
  }

  const nestedTrigger = useTemplateRef<HTMLElement>('nestedTrigger');
  const path = computed(() => props.col.prefixedHandleAsArray);

  const mapValue = computed<string>(() => {
    const value = getAt(context.values.map, path.value);

    return value === null || value === undefined ? '' : String(value);
  });

  const isBestGuess = computed(() =>
    Boolean(getAt(context.suggestedMap, path.value))
  );

  /**
   * A container has no value of its own to match on or clear — each of its nested
   * columns carries its own decision, inside the panel.
   */
  const canMatch = computed(
    () => !props.col.isContainer && props.col.canBeMatchCriteria
  );

  const canClear = computed(
    () => !props.col.isContainer && props.col.canBeCleared
  );

  const isMatchOnly = computed(
    () => !props.col.isContainer && props.col.canBeSet === false
  );

  const matchCriteriaChecked = computed(() =>
    isChecked(getAt(context.values.matchCriteria, path.value))
  );

  const clearChecked = computed(() =>
    isChecked(getAt(context.values.clearableItems, path.value))
  );

  function onMapChanged(event: CustomEvent): void {
    if (event.detail?.initialize) {
      return;
    }

    const value = (event.target as HTMLElement & {modelValue?: unknown})
      .modelValue;

    setAt(context!.values.map, path.value, value == null ? '' : String(value));
    setAt(context!.suggestedMap, path.value, false);
  }

  // `craft-checkbox` announces its starting state too, which would write to the
  // trees before the user has touched anything and read as an unsaved change.
  const onMatchCriteriaChanged = ignoreModelValueInitialization((event) => {
    setAt(context!.values.matchCriteria, path.value, checkedValue(event));
  });

  const onClearChanged = ignoreModelValueInitialization((event) => {
    setAt(context!.values.clearableItems, path.value, checkedValue(event));
  });

  function openNested(): void {
    context!.openNested(props.col, nestedTrigger.value);
  }

  /** Whether the container's nested mapping has anything in it yet. */
  const hasNestedMapping = computed(() => {
    const branch = getAt(context!.values.map, path.value);

    return (
      branch !== null &&
      typeof branch === 'object' &&
      Object.keys(branch as object).length > 0
    );
  });
</script>

<template>
  <tr>
    <th scope="row">
      {{ col.label }}
      <craft-badge v-if="isMatchOnly" fill="gray">{{
        t('Match only')
      }}</craft-badge>
      <craft-info-icon v-if="isMatchOnly">{{
        t(
          'This value can’t be imported directly — it can only be used to match against an existing element.'
        )
      }}</craft-info-icon>
      <br />
      <code>{{ col.handle }}</code>
    </th>
    <td :class="{'best-guess': isBestGuess}">
      <craft-button
        v-if="col.isContainer"
        ref="nestedTrigger"
        type="button"
        :variant="ButtonVariant.Dashed"
        :disabled="!context.editable"
        @click="openNested"
      >
        {{ hasNestedMapping ? t('Edit mapping') : t('Map field') }}
      </craft-button>
      <CraftCombobox
        v-else
        :model-value="mapValue"
        :options="context.sourceDataCols"
        :disabled="!context.editable"
        :label="col.label"
        label-sr-only
        require-option-match
        show-all-on-empty
        @model-value-changed="onMapChanged"
      />
    </td>

    <!-- The column header names these; the row is named by its `th`. An empty
         cell means the option can never apply to this field, which is why it isn't
         a disabled checkbox. `label-sr-only` + a slotted label is the only way to
         name a `craft-checkbox` — Lion overwrites `aria-labelledby` on the inner
         input with its own generated label. -->
    <td>
      <craft-checkbox
        v-if="canMatch"
        label-sr-only
        .checked="matchCriteriaChecked"
        :disabled="!context.editable"
        @model-value-changed="onMatchCriteriaChanged"
      >
        <label slot="label">{{
          t('Use this field’s value to match against an existing element.')
        }}</label>
      </craft-checkbox>
    </td>

    <td>
      <craft-checkbox
        v-if="canClear"
        label-sr-only
        .checked="clearChecked"
        :disabled="!context.editable"
        @model-value-changed="onClearChanged"
      >
        <label slot="label">{{
          t(
            'Clear existing value if no data provided or provided value is empty.'
          )
        }}</label>
      </craft-checkbox>
    </td>
  </tr>
</template>

<style scoped lang="scss">
  .best-guess {
    background: var(--color-blue-100);
  }
</style>
