<script setup lang="ts">
  import CraftSelectColor from '@craftcms/ui/components/select-color/select-color';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  // The select uses __blank__ internally; form values use an empty string.
  const BLANK_VALUE = '__blank__';

  type ColorSelectControlProps = {
    allowTransparent?: boolean;
    blankLabel?: string;
    colors?: string[];
  };

  defineProps<{
    control: UiControlPayload<ColorSelectControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'discrete'): void;
  }>();

  function onChange(event: Event): void {
    if (!(event.currentTarget instanceof CraftSelectColor)) {
      throw new TypeError('Expected a color select event target.');
    }

    const value = event.currentTarget.value;
    emit(
      'update:value',
      value === BLANK_VALUE ? '' : String(value ?? ''),
      'discrete'
    );
  }
</script>

<template>
  <craft-select-color
    :name="editable ? inputName(control.path) : ''"
    .value="
      !value && control.props.allowTransparent
        ? BLANK_VALUE
        : String(value ?? '')
    "
    .allowTransparent="control.props.allowTransparent === true"
    :blank-label="control.props.blankLabel"
    :colors="control.props.colors ?? undefined"
    .required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    :invalid="invalid"
    @change="onChange"
  ></craft-select-color>
</template>
