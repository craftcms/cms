<script setup lang="ts">
  /** The field type's own per-field choices, such as what to do with a conflicting file. */
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/info-icon/info-icon';
  import '@craftcms/ui/components/select/select';
  import {getAt, setAt} from '../paths';
  import {
    useMappingContext,
    type FieldMappingSetting,
    type MappingColumn,
  } from '../types';

  const {col, settings} = defineProps<{
    col: MappingColumn;
    settings: FieldMappingSetting[];
  }>();

  const context = useMappingContext();

  function settingPath(setting: FieldMappingSetting): string[] {
    return [...col.prefixedHandleAsArray, setting.name];
  }

  function valueOf(setting: FieldMappingSetting): string {
    const value = getAt(context.values.fieldSettings, settingPath(setting));

    return value === null || value === undefined || value === ''
      ? setting.default
      : String(value);
  }

  function onChange(setting: FieldMappingSetting, event: Event): void {
    const value = (event.currentTarget as {value?: unknown}).value;

    setAt(
      context.values.fieldSettings,
      settingPath(setting),
      value == null ? setting.default : String(value)
    );
  }
</script>

<template>
  <craft-field
    v-for="setting in settings"
    :key="setting.name"
    class="import-settings"
    layout="inline"
  >
    <!-- A span, not a label: the info icon is a button, which a label would also activate. -->
    <span slot="label">
      {{ setting.label }}
      <craft-info-icon v-if="setting.instructions">{{
        setting.instructions
      }}</craft-info-icon>
    </span>
    <craft-select
      slot="input"
      :label="setting.label"
      label-sr-only
      .value="valueOf(setting)"
      .disabled="!context.editable"
      @change="onChange(setting, $event)"
    >
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
  </craft-field>
</template>

<style scoped>
  .import-settings {
    /* the cell is narrow, so the label and select share it evenly */
    --c-field-inline-label-size: fit-content(50%);
    margin-block-start: var(--c-spacing-md);
  }

  /* runs as text, so a wrapped label keeps its info icon after the last word */
  .import-settings > [slot='label'] {
    display: block;
  }

  .import-settings craft-info-icon {
    display: inline-flex;
    vertical-align: middle;
  }
</style>
