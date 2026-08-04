<script setup lang="ts">
  import CraftSelect from '@craftcms/ui/components/select/select';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type SelectControlProps = {
    options: Array<{label: string; value: boolean | number | string}>;
  };

  defineProps<{
    control: FormControlPayload<SelectControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{(event: 'update:value', value: string): void}>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    emit(
      'update:value',
      String((event.target as CraftSelect).modelValue ?? '')
    );
  });
</script>

<template>
  <craft-select
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : ''"
    .modelValue="String(value ?? '')"
    :required="editable && required"
    :disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  >
    <select slot="input">
      <option
        v-for="option in control.props.options"
        :key="String(option.value)"
        :value="String(option.value)"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>
</template>
