<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type TextControlProps = {
    inputType?: string;
    min?: number;
    maxLength?: number;
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

  function onModelValueChanged(event: Event): void {
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }

    emit(
      'update:value',
      String((event.target as CraftInput).modelValue ?? ''),
      'typing'
    );
  }
</script>

<template>
  <craft-input
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : undefined"
    :type="control.props.inputType ?? 'text'"
    .modelValue="String(value ?? '')"
    :min="control.props.min"
    :maxlength="control.props.maxLength"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-input>
</template>
