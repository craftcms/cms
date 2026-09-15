<script setup lang="ts">
  import CraftSelectColor from '@craftcms/ui/components/select-color/select-color';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  // The underlying <craft-select-color> uses this sentinel internally for its
  // blank ("no color") option's choiceValue, since a rich select's options
  // need a real, distinct value to match against. Our own canonical "no
  // color" value stays the empty string (the same convention every other
  // select-like control in this Form system uses), so it's translated at the
  // boundary in both directions rather than leaking out to the server.
  const BLANK_VALUE = '__blank__';

  type ColorSelectControlProps = {
    allowTransparent?: boolean;
    blankLabel?: string;
    colors?: string[];
  };

  defineProps<{
    control: FormControlPayload<ColorSelectControlProps>;
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
    if (!(event.target instanceof CraftSelectColor)) {
      throw new TypeError('Expected a color select event target.');
    }

    const modelValue = event.target.modelValue;
    emit(
      'update:value',
      modelValue === BLANK_VALUE ? '' : String(modelValue ?? ''),
      'discrete'
    );
  });
</script>

<template>
  <craft-select-color
    :name="editable ? inputName(control.path) : ''"
    .modelValue="
      !value && control.props.allowTransparent
        ? BLANK_VALUE
        : String(value ?? '')
    "
    :allow-transparent="control.props.allowTransparent ?? false"
    :blank-label="control.props.blankLabel"
    :colors="control.props.colors ?? undefined"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-select-color>
</template>
