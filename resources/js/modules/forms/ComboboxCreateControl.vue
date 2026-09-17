<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import type CraftComboboxCreate from './combobox-create';
  import './combobox-create';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ComboboxCreateControlProps = {
    options: ComboboxItem[];
    multiple?: boolean;
    createUrl?: string;
    createValue?: string;
    resultKey?: string;
    labelField?: string;
    valueField?: string;
    placeholder?: string;
    limit?: number;
    clearable?: boolean;
    requireOptionMatch?: boolean;
    showAllOnEmpty?: boolean;
    showSelectedHint?: boolean;
    dir?: string;
  };

  defineProps<{
    control: FormControlPayload<ComboboxCreateControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string | string[], kind: 'discrete'): void;
  }>();

  function onModelValueChanged(event: Event): void {
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }

    const target = event.target as CraftComboboxCreate;
    const value = target.modelValue;

    // Skip the transient state where the trigger option itself is (still) selected — the
    // control resets that back out on its own right after opening the create slideout.
    const triggered = Array.isArray(value)
      ? value.includes(target.createValue)
      : value === target.createValue;

    if (!triggered) {
      emit(
        'update:value',
        Array.isArray(value) ? value.map(String) : String(value ?? ''),
        'discrete'
      );
    }
  }
</script>

<template>
  <craft-combobox-create
    :name="editable ? inputName(control.path) : ''"
    :multiple-choice="control.props.multiple ?? false"
    .modelValue="
      control.props.multiple
        ? Array.isArray(value)
          ? value.map(String)
          : []
        : String(value ?? '')
    "
    .options="control.props.options"
    .createUrl="control.props.createUrl ?? ''"
    .createValue="control.props.createValue ?? '__add__'"
    .resultKey="control.props.resultKey ?? ''"
    .labelField="control.props.labelField ?? 'name'"
    .valueField="control.props.valueField ?? 'id'"
    :placeholder="control.props.placeholder"
    .limit="control.props.limit ?? 150"
    .clearable="control.props.clearable ?? false"
    .requireOptionMatch="control.props.requireOptionMatch ?? false"
    .showAllOnEmpty="control.props.showAllOnEmpty ?? false"
    .showSelectedHint="control.props.showSelectedHint ?? false"
    :dir="control.props.dir"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  />
</template>
