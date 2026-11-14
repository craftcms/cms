<script setup lang="ts">
  /**
   * A destination column's default value, entered with the input its field would use,
   * with the field's own settings: its options, limits, and labels.
   */
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input-color/input-color';
  import '@craftcms/ui/components/input-date/input-date';
  import '@craftcms/ui/components/input-time/input-time';
  import '@craftcms/ui/components/select/select';
  import '@craftcms/ui/components/switch/switch';
  import '@craftcms/ui/components/textarea/textarea';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {isInitialization} from '@/common/table/cells/types';
  import {
    defaultValueInputOf,
    defaultValueOf,
    defaultValuesOf,
    setDefaultValue,
  } from '../empty-values';
  import {useMappingContext, type MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  const input = computed(() => defaultValueInputOf(col));
  const value = computed(() => defaultValueOf(context.values, col));
  const values = computed(() => defaultValuesOf(context.values, col));
  const label = t('Default value');

  type ChangeTarget = HTMLElement & {modelValue?: unknown; checked?: boolean};

  function onChange(event: Event): void {
    // a checkbox group's checkboxes announce their own changes too
    if (isInitialization(event) || event.target !== event.currentTarget) {
      return;
    }

    const target = event.currentTarget as ChangeTarget;

    switch (input.value.type) {
      case 'lightswitch':
        setDefaultValue(context.values, col, target.checked ? '1' : '0');
        break;
      case 'multiselect':
        setDefaultValue(
          context.values,
          col,
          JSON.stringify(
            Array.isArray(target.modelValue) ? target.modelValue : []
          )
        );
        break;
      default:
        setDefaultValue(
          context.values,
          col,
          target.modelValue == null ? '' : String(target.modelValue)
        );
    }
  }
</script>

<template>
  <craft-switch
    v-if="input.type === 'lightswitch'"
    :label="label"
    :on-label="input.onLabel"
    :off-label="input.offLabel"
    .checked="value === '1'"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  ></craft-switch>

  <craft-select
    v-else-if="input.type === 'select'"
    :label="label"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  >
    <select slot="input">
      <option value="">{{ t('Choose an option') }}</option>
      <option
        v-for="option in input.options ?? []"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>

  <craft-checkbox-group
    v-else-if="input.type === 'multiselect'"
    .modelValue="values"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  >
    <span slot="label">{{ label }}</span>
    <craft-checkbox
      v-for="option in input.options ?? []"
      :key="option.value"
      .choiceValue="option.value"
      .checked="values.includes(option.value)"
    >
      <span slot="label">{{ option.label }}</span>
    </craft-checkbox>
  </craft-checkbox-group>

  <craft-textarea
    v-else-if="input.type === 'textarea'"
    :label="label"
    :placeholder="input.placeholder"
    :maxlength="input.maxLength"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  ></craft-textarea>

  <craft-input-date
    v-else-if="input.type === 'date'"
    :label="label"
    :min="input.min"
    :max="input.max"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  ></craft-input-date>

  <craft-input-time
    v-else-if="input.type === 'time'"
    :label="label"
    :min="input.min"
    :max="input.max"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  ></craft-input-time>

  <craft-input-color
    v-else-if="input.type === 'color'"
    :label="label"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  ></craft-input-color>

  <craft-input
    v-else
    :type="input.type === 'datetime' ? 'datetime-local' : input.type"
    :label="label"
    :placeholder="input.placeholder"
    :maxlength="input.maxLength"
    :min="input.min"
    :max="input.max"
    :step="input.step"
    .modelValue="value"
    .disabled="!context.editable"
    @model-value-changed="onChange"
  >
    <span v-if="input.prefix" slot="prefix">{{ input.prefix }}</span>
    <span v-if="input.suffix" slot="suffix">{{ input.suffix }}</span>
  </craft-input>
</template>
