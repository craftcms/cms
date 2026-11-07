<script setup lang="ts">
  import CraftInputColor from '@craftcms/ui/components/input-color/input-color';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  type ColorControlProps = {presets: string[]; alpha?: boolean};

  defineProps<{
    control: UiControlPayload<ColorControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'discrete'): void;
  }>();

  function onInput(event: Event): void {
    const input = event.currentTarget;
    if (!(input instanceof CraftInputColor)) {
      throw new TypeError('Expected a color input event target.');
    }

    emit('update:value', input.value, 'discrete');
  }
</script>

<template>
  <craft-input-color
    :name="editable ? inputName(control.path) : ''"
    .value="String(value ?? '')"
    .presets="control.props.presets"
    .alpha="control.props.alpha ?? false"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    :invalid="invalid"
    @input="onInput"
  ></craft-input-color>
</template>
