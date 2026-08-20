<script setup lang="ts">
  import CraftInputColor from '@craftcms/ui/components/input-color/input-color';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type ColorControlProps = {presets: string[]};

  defineProps<{
    control: FormControlPayload<ColorControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'discrete'): void;
  }>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    if (!(event.target instanceof CraftInputColor)) {
      throw new TypeError('Expected a color input event target.');
    }

    emit('update:value', String(event.target.modelValue ?? ''), 'discrete');
  });
</script>

<template>
  <craft-input-color
    :name="editable ? inputName(control.path) : ''"
    .modelValue="String(value ?? '')"
    .presets="control.props.presets"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-input-color>
</template>
