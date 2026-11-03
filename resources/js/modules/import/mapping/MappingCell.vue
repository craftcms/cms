<script setup lang="ts">
  /**
   * One cell of a destination column's row: what it maps to, and how it behaves when
   * the imported value is missing. The row's cells share their state through the
   * mapping trees, so each renders independently.
   *
   * A container column has no row of its own — `MappingTable` maps it in a section
   * below, over the same trees — which is why every control here writes through
   * `prefixedHandleAsArray` rather than a form input name.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/info-icon/info-icon';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/select/select';
  import '@craftcms/ui/components/select-rich/select-rich';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {computed, inject, ref, useId, watch} from 'vue';
  import {Color, t} from '@craftcms/ui';
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
    part: 'destination' | 'incoming' | 'match' | 'clear';
    /** The compound column this one is a subfield of, if any. */
    heading?: string | null;
  }>();

  const context = inject(MappingContextKey);

  if (!context) {
    throw new Error('MappingCell must be rendered inside a mapping screen.');
  }

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
   * The column the server suggested, which its option goes on saying in the list
   * after another is chosen. A container's suggestions arrive after the row renders.
   */
  const suggestedColumn = ref<string | null>(null);

  watch(
    isBestGuess,
    (guessed) => {
      if (guessed) {
        suggestedColumn.value = mapValue.value;
      }
    },
    {immediate: true}
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
</script>

<template>
  <template v-if="part === 'destination'">
    <span class="destination">
      <span v-if="heading" class="text-quiet">{{ heading }} ›</span>
      {{ col.label }}
      <craft-badge v-if="isMatchOnly" fill="gray" size="small" no-prefix>{{
        t('Match only')
      }}</craft-badge>
    </span>
    <code class="block font-normal text-xs text-quiet">{{ col.handle }}</code>
  </template>

  <div v-else-if="part === 'incoming'">
    <template v-if="!col.isContainer">
      <!-- The suggested option's badge is appended to the select's value too while it's selected. -->
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
          <craft-badge
            v-if="suggestedColumn !== null && option.value === suggestedColumn"
            slot="badge"
            :fill="Color.Accent"
            ^size="small"
            no-prefix
            >{{ t('Suggested') }}</craft-badge
          >
        </craft-option>
      </craft-select-rich>
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
        <span
          v-if="context.editable"
          slot="tip"
          v-html="t('Type <kbd>{</kbd> to choose an object template variable.')"
        ></span>
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
  </div>

  <!-- A slotted label is the only way to name a `craft-checkbox`: Lion overwrites `aria-labelledby`. -->
  <craft-checkbox
    v-else-if="part === 'match' && canMatch"
    label-sr-only
    .checked="matchCriteriaChecked"
    .disabled="!context.editable || mapValue === ''"
    @model-value-changed="onMatchCriteriaChanged"
  >
    <label slot="label">{{ t('Match on {field}', {field: col.label}) }}</label>
  </craft-checkbox>

  <craft-checkbox
    v-else-if="part === 'clear' && canClear"
    label-sr-only
    .checked="clearChecked"
    .disabled="!context.editable"
    @model-value-changed="onClearChanged"
  >
    <label slot="label">{{ t('Clear {field}', {field: col.label}) }}</label>
  </craft-checkbox>
</template>

<style scoped lang="scss">
  // the name and its badge share a line, centered on each other rather than on the text's baseline
  .destination {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    column-gap: var(--c-spacing-sm);
  }

  .heading {
    display: block;
    font-size: 0.85em;
    color: var(--c-text-quiet);
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
