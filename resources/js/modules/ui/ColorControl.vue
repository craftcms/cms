<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
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

  // The select's own values for "no color" and "Custom…". The server reads
  // them as Craft 5's Color field posted them.
  const BLANK_VALUE = '__blank__';
  const CUSTOM_VALUE = '__custom__';

  type ColorControlProps = {
    presets: string[];
    alpha?: boolean;
    custom?: boolean;
    presetLabels?: Record<string, string>;
  };

  // The field's attributes, such as `slot="input"`, belong on the main
  // control, not on the custom color beside it.
  defineOptions({inheritAttrs: false});

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

  const name = computed(() =>
    props.editable ? inputName(props.control.path) : ''
  );
  const value = computed(() => String(props.value ?? ''));
  const presets = computed(() => props.control.props.presets);
  const customAllowed = computed(() => props.control.props.custom !== false);

  /**
   * A palette is picked from a select, which offers "Custom…" when custom
   * colors are allowed. Without a palette, any color is typed in.
   */
  const usesSelect = computed(
    () => presets.value.length > 0 || !customAllowed.value
  );

  /** The preset that's the value's color, however the value is written. */
  const selectedPreset = computed(() =>
    value.value === ''
      ? ''
      : (presets.value.find((preset) => sameColor(preset, value.value)) ??
        value.value)
  );

  const isPreset = (color: string) =>
    presets.value.some((preset) => sameColor(preset, color));

  /**
   * Whether "Custom…" is picked. It is for a color that isn't a preset, and
   * stays picked while one is typed in, even a preset's.
   */
  const custom = ref(false);
  let emittedValue: string | null = null;
  watch(
    value,
    (newValue) => {
      // Only a value from elsewhere, such as a pasted one, decides it.
      if (newValue !== emittedValue) {
        custom.value =
          customAllowed.value && newValue !== '' && !isPreset(newValue);
      }
      // Each emitted value is echoed once. A later write of the same value
      // comes from elsewhere.
      emittedValue = null;
    },
    {immediate: true}
  );

  /**
   * A value that isn't a preset and can't be a custom color, such as one saved
   * before custom colors were turned off, is listed too, so the select shows
   * the value it holds.
   */
  const selectColors = computed(() =>
    customAllowed.value ||
    selectedPreset.value === '' ||
    presets.value.includes(selectedPreset.value)
      ? presets.value
      : [...presets.value, selectedPreset.value]
  );

  /**
   * "No color" is offered unless the field is required and holds a color it
   * can keep, as Craft 5's Color field did.
   */
  const allowBlank = computed(
    () =>
      !props.required ||
      value.value === '' ||
      (!customAllowed.value && !isPreset(value.value))
  );

  const selectValue = computed(() =>
    custom.value
      ? CUSTOM_VALUE
      : selectedPreset.value || (allowBlank.value ? BLANK_VALUE : '')
  );

  function update(newValue: string) {
    emittedValue = newValue;
    emit('update:value', newValue, 'discrete');
  }

  const onSelectChanged = ignoreModelValueInitialization((event) => {
    if (!(event.target instanceof CraftSelectColor)) {
      throw new TypeError('Expected a color select event target.');
    }

    const picked = String(event.target.modelValue ?? '');

    // The select echoes a value set from here.
    if (picked === selectValue.value) {
      return;
    }

    // "Custom…" starts from the current color, so picking it changes nothing.
    custom.value = picked === CUSTOM_VALUE;
    if (!custom.value) {
      update(picked === BLANK_VALUE ? '' : picked);
    }
  });

  const onInputChanged = ignoreModelValueInitialization((event) => {
    if (!(event.target instanceof CraftInputColor)) {
      throw new TypeError('Expected a color input event target.');
    }

    update(String(event.target.modelValue ?? ''));
  });
</script>

<template>
  <craft-select-color
    v-if="usesSelect"
    v-bind="$attrs"
    :name="name && customAllowed ? `${name}[color]` : name"
    .modelValue="selectValue"
    .colors="selectColors"
    .labels="control.props.presetLabels ?? {}"
    .allowTransparent="allowBlank"
    :blank-label="t('No color')"
    .allowCustom="customAllowed"
    .required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onSelectChanged"
  ></craft-select-color>
  <craft-input-color
    v-else
    v-bind="$attrs"
    :name="name"
    .modelValue="value"
    .alpha="control.props.alpha ?? false"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onInputChanged"
  ></craft-input-color>
  <craft-input-color
    v-if="usesSelect && custom"
    slot="after"
    class="mt-sm"
    :label="t('Custom color:')"
    :name="name ? `${name}[custom]` : ''"
    .modelValue="value"
    .alpha="control.props.alpha ?? false"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="!editable"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onInputChanged"
  ></craft-input-color>
</template>
