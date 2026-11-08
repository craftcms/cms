<script setup lang="ts">
  import {computed} from 'vue';
  import CraftInputColor from '@craftcms/ui/components/input-color/input-color';
  import CraftSelectColor from '@craftcms/ui/components/select-color/select-color';
  import {t} from '@craftcms/ui/utilities/translate';
  import type {UiControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';
  import {sameColor} from './valueAcceptors';

  // The select uses __blank__ internally; form values use an empty string.
  const BLANK_VALUE = '__blank__';

  type ColorControlProps = {
    presets: string[];
    alpha?: boolean;
    custom?: boolean;
    presetLabels?: Record<string, string>;
  };

  const props = defineProps<{
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

  /** Without custom colors, the color is picked from the presets. */
  const presetsOnly = computed(() => props.control.props.custom === false);

  /** The preset that's the value's color, however the value is written. */
  const selectedPreset = computed(() => {
    const value = String(props.value ?? '');

    return value === ''
      ? ''
      : (props.control.props.presets.find((preset) =>
          sameColor(preset, value)
        ) ?? value);
  });

  /**
   * A value that isn't a preset, such as one saved before custom colors were
   * turned off, is listed too, so the select shows the value it holds.
   */
  const selectColors = computed(() => {
    const presets = props.control.props.presets;

    return selectedPreset.value === '' || presets.includes(selectedPreset.value)
      ? presets
      : [...presets, selectedPreset.value];
  });

  /** "No color" is offered unless the field is required and holds a preset. */
  const allowBlank = computed(
    () =>
      !props.required ||
      !props.control.props.presets.includes(selectedPreset.value)
  );

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    if (event.target instanceof CraftSelectColor) {
      const modelValue = String(event.target.modelValue ?? '');
      emit(
        'update:value',
        modelValue === BLANK_VALUE ? '' : modelValue,
        'discrete'
      );

      return;
    }

    if (!(event.target instanceof CraftInputColor)) {
      throw new TypeError('Expected a color input event target.');
    }

    emit('update:value', String(event.target.modelValue ?? ''), 'discrete');
  });
</script>

<template>
  <craft-select-color
    v-if="presetsOnly"
    :name="editable ? inputName(control.path) : ''"
    .modelValue="selectedPreset || (allowBlank ? BLANK_VALUE : '')"
    .colors="selectColors"
    .labels="control.props.presetLabels ?? {}"
    .allowTransparent="allowBlank"
    :blank-label="t('No color')"
    .required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-select-color>
  <craft-input-color
    v-else
    :name="editable ? inputName(control.path) : ''"
    .modelValue="String(value ?? '')"
    .presets="control.props.presets"
    .alpha="control.props.alpha ?? false"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-input-color>
</template>
