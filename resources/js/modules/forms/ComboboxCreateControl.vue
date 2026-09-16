<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import type CraftComboboxCreate from './combobox-create';
  import './combobox-create';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ComboboxCreateControlProps = {
    options: ComboboxItem[];
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
    (event: 'update:value', value: string, kind: 'discrete'): void;
  }>();

  function onModelValueChanged(event: Event): void {
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }

    const target = event.target as CraftComboboxCreate;

    if (target.modelValue !== target.createValue) {
      emit('update:value', String(target.modelValue ?? ''), 'discrete');
    }
  }
</script>

<template>
  <craft-combobox-create
    :name="editable ? inputName(control.path) : ''"
    .modelValue="String(value ?? '')"
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
