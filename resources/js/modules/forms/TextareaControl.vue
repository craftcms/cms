<script setup lang="ts">
  import CraftTextarea from '@craftcms/ui/components/textarea/textarea';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type TextareaControlProps = {
    rows?: number;
    maxLength?: number;
    placeholder?: string;
    monospace?: boolean;
  };

  defineProps<{
    control: FormControlPayload<TextareaControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'typing'): void;
  }>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    emit(
      'update:value',
      String((event.target as CraftTextarea).modelValue ?? ''),
      'typing'
    );
  });
</script>

<template>
  <craft-textarea
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : undefined"
    .modelValue="String(value ?? '')"
    :rows="control.props.rows ?? 2"
    :maxlength="control.props.maxLength"
    :placeholder="control.props.placeholder"
    :monospace="control.props.monospace"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  >
    <textarea
      slot="input"
      :name="editable ? inputName(control.path) : undefined"
      :value="String(value ?? '')"
      :rows="control.props.rows ?? 2"
      :maxlength="control.props.maxLength"
      :placeholder="control.props.placeholder"
      :required="editable && required"
      :readonly="control.mode === 'readOnly'"
      :disabled="control.mode === 'disabled'"
      :aria-invalid="invalid ? 'true' : undefined"
    ></textarea>
  </craft-textarea>
</template>
