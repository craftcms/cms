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
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/info-icon/info-icon';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/select/select';
  import '@craftcms/ui/components/select-rich/select-rich';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {computed, inject, useId, useTemplateRef} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/ui/runtime';
  import {checkedValue, getAt, isChecked, setAt} from './paths';
  import {
    type FieldMappingSetting,
    type MappingColumn,
    MappingContextKey,
    TEMPLATE_RULE,
  } from './types';

  const props = defineProps<{
    col: MappingColumn;
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error('MappingRow must be rendered inside a mapping screen.');
  }

  const nestedTrigger = useTemplateRef<HTMLElement>('nestedTrigger');

  /** Reads a column's leaf in one of the trees as a string, with nothing set reading as `''`. */
  function stringAt(
    tree: Record<string, unknown>,
    columnPath: string[]
  ): string {
    const value = getAt(tree, columnPath);

    return value === null || value === undefined ? '' : String(value);
  }
  const path = computed(() => props.col.prefixedHandleAsArray);

  const mapValue = computed(() => stringAt(context.values.map, path.value));

  /** Whether the column is filled from a template rather than a source column. */
  const isTemplate = computed(() => mapValue.value === TEMPLATE_RULE);

  const templateInputId = useId();

  const templateValue = computed(() =>
    stringAt(context.values.templates, path.value)
  );

  /** How the server's template errors are keyed: the column's path joined with dots. */
  const templateErrorKey = computed(() => path.value.join('.'));

  const templateErrors = computed<string[]>(
    () => context.templateErrors.value[templateErrorKey.value] ?? []
  );

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

    const next = value == null ? '' : String(value);

    setAt(context!.values.map, path.value, next);
    setAt(context!.suggestedMap, path.value, false);

    // only a mapped column can be matched on, so unmapping it drops its match criterion too
    if (next === '' && matchCriteriaChecked.value) {
      setAt(context!.values.matchCriteria, path.value, '');
    }
  }

  /**
   * Kept when the column is switched to another option, so switching back doesn't
   * lose it. The server only reads a template while the column is mapped to one.
   */
  const onTemplateChanged = ignoreModelValueInitialization((event) => {
    const value = (event.target as HTMLElement & {modelValue?: unknown})
      .modelValue;

    setAt(
      context!.values.templates,
      path.value,
      value == null ? '' : String(value)
    );
    delete context!.templateErrors.value[templateErrorKey.value];
  });

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
        <craft-field
          v-if="isTemplate"
          class="template"
          :label="t('Template')"
          :has-errors="templateErrors.length > 0"
          width="full"
          required
        >
          <craft-input
            slot="input"
            monospace
            .modelValue="templateValue"
            required
            :disabled="!context.editable"
            :aria-invalid="templateErrors.length ? 'true' : undefined"
            @model-value-changed="onTemplateChanged"
          >
            <input
              :id="templateInputId"
              slot="input"
              :data-mapping-template-invalid="
                templateErrors.length ? context.panelId : undefined
              "
            />
          </craft-input>
          <craft-text-expander
            v-if="context.editable"
            slot="input"
            :for="templateInputId"
            .triggers="context.templateTextExpanderTriggers"
          />
          <craft-info-icon
            v-if="context.editable"
            slot="heading-suffix"
            :label="
              t('More info about {name}', {
                name: `${t('Template')} ${col.label}`,
              })
            "
          >
            <span
              v-html="
                t('Type <kbd>{</kbd> to choose an object template variable.')
              "
            ></span>
          </craft-info-icon>
          <ul v-if="templateErrors.length" slot="feedback" class="error-list">
            <li v-for="error in templateErrors" :key="error">{{ error }}</li>
          </ul>
        </craft-field>
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
        .disabled="!context.editable || mapValue === ''"
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

  .template {
    margin-block-start: var(--c-spacing-md);
    white-space: normal;
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
