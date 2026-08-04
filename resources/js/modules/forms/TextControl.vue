<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type TextControlProps = {
    inputType?: string;
    min?: number | string;
    max?: number | string;
    step?: number | string;
    maxLength?: number;
    placeholder?: string;
    monospace?: boolean;
  };

  defineProps<{
    control: FormControlPayload<TextControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: FormChangeKind): void;
  }>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    emit(
      'update:value',
      String((event.target as CraftInput).modelValue ?? ''),
      ['text', 'email', 'url', 'tel', 'password'].includes(
        String((event.target as CraftInput).type)
      )
        ? 'typing'
        : 'discrete'
    );
  });
</script>

<template>
  <craft-input
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : ''"
    :type="control.props.inputType ?? 'text'"
    .modelValue="String(value ?? '')"
    :min="control.props.min"
    :max="control.props.max"
    :step="control.props.step"
    :maxlength="control.props.maxLength"
    :placeholder="control.props.placeholder"
    :monospace="control.props.monospace"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-input>
</template>
