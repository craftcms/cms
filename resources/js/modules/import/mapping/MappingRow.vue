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
  import '@craftcms/ui/components/select/select';
  import '@craftcms/ui/components/select-rich/select-rich';
  import {computed, inject, useTemplateRef} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/forms/runtime';
  import {checkedValue, getAt, isChecked, setAt} from './paths';
  import {
    type FieldMappingSetting,
    type MappingColumn,
    MappingContextKey,
  } from './types';

  const props = defineProps<{
    col: MappingColumn;
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

  /** The field type's own per-field choices, shown once the column is mapped. */
  const importSettings = computed<FieldMappingSetting[]>(() =>
    !props.col.isContainer && mapValue.value !== ''
      ? (props.col.importSettings ?? [])
      : []
  );

  function importSettingValue(setting: FieldMappingSetting): string {
    const value = getAt(context!.values.fieldSettings, [
      ...path.value,
      setting.name,
    ]);

    return value === null || value === undefined || value === ''
      ? setting.default
      : String(value);
  }

  function onImportSettingChanged(
    setting: FieldMappingSetting,
    event: Event
  ): void {
    ignoreModelValueInitialization(() => {
      const value = (event.target as HTMLElement & {modelValue?: unknown})
        .modelValue;

      setAt(
        context!.values.fieldSettings,
        [...path.value, setting.name],
        value == null ? setting.default : String(value)
      );
    })(event);
  }

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
        .disabled="!context.editable"
        @click="openNested"
      >
        {{ hasNestedMapping ? t('Edit mapping') : t('Map field') }}
      </craft-button>
      <template v-else>
        <craft-select-rich
          .modelValue="mapValue"
          .disabled="!context.editable"
          :label="col.label"
          label-sr-only
          @model-value-changed="onMapChanged"
        >
          <craft-option
            v-for="option in context.sourceDataCols"
            :key="option.value"
            .choiceValue="option.value"
            .hint="option.hint ?? null"
          >
            {{ option.label }}
          </craft-option>
          <span v-if="isBestGuess" slot="help-text" class="sr-only">{{
            t('Suggested')
          }}</span>
        </craft-select-rich>
        <div v-if="isBestGuess" class="suggested">
          <craft-badge fill="blue" aria-hidden="true">{{
            t('Suggested')
          }}</craft-badge>
        </div>
      </template>
      <div v-if="importSettings.length" class="import-settings">
        <craft-select
          v-for="setting in importSettings"
          :key="setting.name"
          .modelValue="importSettingValue(setting)"
          .disabled="!context.editable"
          @model-value-changed="onImportSettingChanged(setting, $event)"
        >
          <!-- A span, not a label: the info icon is a button, which a label would also activate. -->
          <span slot="label">
            {{ setting.label }}
            <craft-info-icon v-if="setting.instructions">{{
              setting.instructions
            }}</craft-info-icon>
          </span>
          <select slot="input">
            <option
              v-for="option in setting.options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </craft-select>
      </div>
    </td>

    <!-- A slotted label is the only way to name a `craft-checkbox`: Lion overwrites `aria-labelledby`. -->
    <td>
      <craft-checkbox
        v-if="canMatch"
        label-sr-only
        .checked="matchCriteriaChecked"
        .disabled="!context.editable"
        @model-value-changed="onMatchCriteriaChanged"
      >
        <label slot="label">{{
          t('Match on {field}', {field: col.label})
        }}</label>
      </craft-checkbox>
    </td>

    <td>
      <craft-checkbox
        v-if="canClear"
        label-sr-only
        .checked="clearChecked"
        .disabled="!context.editable"
        @model-value-changed="onClearChanged"
      >
        <label slot="label">{{ t('Clear {field}', {field: col.label}) }}</label>
      </craft-checkbox>
    </td>
  </tr>
</template>

<style scoped lang="scss">
  .best-guess {
    background: var(--color-blue-100);
  }

  .suggested {
    display: flex;
    justify-content: flex-end;
    margin-block-start: var(--c-spacing-sm);
  }

  .import-settings {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    margin-block-start: var(--c-spacing-md);
    margin-inline-start: var(--c-spacing-lg);
    padding: var(--c-spacing-md);
    padding-inline-end: 0;
  }
</style>
