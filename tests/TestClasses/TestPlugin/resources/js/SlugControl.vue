<script setup lang="ts">
  import {inputName} from '@/modules/forms/runtime';
  import type {FormChangeKind, FormControlPayload} from '@/modules/forms/types';

  defineProps<{
    control: FormControlPayload<{placeholder: string}>;
    value: unknown;
    editable: boolean;
    label?: string;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: FormChangeKind): void;
  }>();
</script>

<template>
  <input
    data-test-plugin-control
    :name="editable ? inputName(control.path) : undefined"
    :value="String(value ?? '')"
    :placeholder="control.props.placeholder"
    :aria-label="label"
    :aria-invalid="invalid ? 'true' : undefined"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    @input="
      emit('update:value', ($event.target as HTMLInputElement).value, 'typing')
    "
  />
</template>
