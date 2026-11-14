<script setup lang="ts">
  /** The field type's own per-field choices, such as what to do with a conflicting file. */
  import '@craftcms/ui/components/info-icon/info-icon';
  import '@craftcms/ui/components/select/select';
  import {isInitialization} from '@/common/table/cells/types';
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
    if (isInitialization(event)) {
      return;
    }

    const value = (event.currentTarget as {modelValue?: unknown}).modelValue;

    setAt(
      context.values.fieldSettings,
      settingPath(setting),
      value == null ? setting.default : String(value)
    );
  }
</script>

<template>
  <div class="import-settings">
    <craft-select
      v-for="setting in settings"
      :key="setting.name"
      .modelValue="valueOf(setting)"
      .disabled="!context.editable"
      @model-value-changed="onChange(setting, $event)"
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
</template>

<style scoped>
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
