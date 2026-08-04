<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/radio/radio';
  import '@craftcms/ui/components/radio-group/radio-group';
  import '@craftcms/ui/components/select/select';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ChoiceValue = boolean | number | string;
  type ChoicePresentation = CraftCms.Cms.Form.Enums.ChoicePresentation;
  type ChoiceControlProps = {
    options: Array<{label: string; value: ChoiceValue; disabled?: boolean}>;
    multiple: boolean;
    presentation: ChoicePresentation;
  };

  const props = defineProps<{
    control: FormControlPayload<ChoiceControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string | string[]): void;
  }>();

  function inputValue(value: unknown): string {
    return value === true ? '1' : value === false ? '' : String(value);
  }

  function selected(value: ChoiceValue): boolean {
    const values = props.control.props.multiple
      ? (props.value as unknown[])
      : [props.value];

    return (values ?? []).map(inputValue).includes(inputValue(value));
  }

  function onSelect(event: Event): void {
    const select = event.target as HTMLSelectElement;
    emit(
      'update:value',
      props.control.props.multiple
        ? Array.from(select.selectedOptions, (option) => option.value)
        : select.value
    );
  }

  function onOptionChanged(event: Event): void {
    const input = event.target as HTMLInputElement;

    if (!props.control.props.multiple) {
      emit('update:value', input.value);
      return;
    }

    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      input.checked
        ? [...values, input.value]
        : values.filter((value) => value !== input.value)
    );
  }

  function onButtonClicked(value: ChoiceValue): void {
    const optionValue = inputValue(value);

    if (!props.control.props.multiple) {
      emit('update:value', optionValue);
      return;
    }

    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      values.includes(optionValue)
        ? values.filter((value) => value !== optionValue)
        : [...values, optionValue]
    );
  }

  function optionId(index: number): string {
    return `form-${props.control.path.join('-')}-${index}`;
  }
</script>

<template>
  <craft-select
    v-if="control.props.presentation === 'select'"
    :label="label"
    label-sr-only
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : undefined
    "
    :disabled="!editable"
    :required="editable && required"
    .validators="serverErrorValidators(invalid)"
  >
    <select
      slot="input"
      :name="
        editable
          ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
          : undefined
      "
      :multiple="control.props.multiple"
      :disabled="!editable"
      :required="editable && required"
      :aria-invalid="invalid ? 'true' : undefined"
      @change="onSelect"
    >
      <option
        v-for="option in control.props.options"
        :key="inputValue(option.value)"
        :value="inputValue(option.value)"
        :selected="selected(option.value)"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>
  <craft-button-group
    v-else-if="control.props.presentation === 'buttons'"
    :role="control.props.multiple ? 'group' : 'radiogroup'"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
  >
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      :value="control.props.multiple ? '' : inputValue(value)"
    />
    <input
      v-for="option in control.props.multiple
        ? control.props.options.filter((option) => selected(option.value))
        : []"
      :key="inputValue(option.value)"
      type="hidden"
      :name="`${inputName(control.path)}[]`"
      :value="inputValue(option.value)"
    />
    <craft-button
      v-for="option in control.props.options"
      :key="inputValue(option.value)"
      type="button"
      :value="inputValue(option.value)"
      :active="selected(option.value)"
      :disabled="!editable || option.disabled"
      @click="onButtonClicked(option.value)"
    >
      {{ option.label }}
    </craft-button>
  </craft-button-group>
  <component
    :is="control.props.multiple ? 'craft-checkbox-group' : 'craft-radio-group'"
    v-else
    :label="label"
    label-sr-only
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : undefined
    "
    :required="editable && required"
    :disabled="!editable"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
    .validators="serverErrorValidators(invalid)"
  >
    <input
      v-if="control.props.multiple && editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <component
      :is="control.props.multiple ? 'craft-checkbox' : 'craft-radio'"
      v-for="(option, index) in control.props.options"
      :key="inputValue(option.value)"
      :disabled="!editable || option.disabled"
      .choiceValue="inputValue(option.value)"
      .checked="selected(option.value)"
    >
      <input
        slot="input"
        :id="optionId(index)"
        :type="control.props.multiple ? 'checkbox' : 'radio'"
        :name="
          editable
            ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
            : undefined
        "
        :value="inputValue(option.value)"
        :checked="selected(option.value)"
        :disabled="!editable || option.disabled"
        :required="editable && required && !control.props.multiple"
        :aria-invalid="invalid ? 'true' : undefined"
        @change="onOptionChanged"
      />
      <label slot="label" :for="optionId(index)">{{ option.label }}</label>
    </component>
  </component>
</template>
