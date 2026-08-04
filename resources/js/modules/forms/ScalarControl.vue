<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type ScalarControlProps = {
    inputType: 'date' | 'number' | 'range' | 'time';
    min?: number | string;
    max?: number | string;
    step?: number | string;
    size?: number;
  };

  defineProps<{
    control: FormControlPayload<ScalarControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: FormChangeKind): void;
  }>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    const input = event.target as CraftInput;
    emit(
      'update:value',
      String(input.modelValue ?? ''),
      input.type === 'number' ? 'typing' : 'discrete'
    );
  });
</script>

<template>
  <craft-input
    :label="label"
    label-sr-only
    :type="control.props.inputType"
    :name="editable ? inputName(control.path) : undefined"
    .modelValue="String(value ?? '')"
    :min="control.props.min"
    :max="control.props.max"
    :step="control.props.step"
    .inputSize="control.props.size"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="
      control.mode === 'disabled' ||
      (control.mode === 'readOnly' && control.props.inputType === 'range')
    "
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-input>
</template>
