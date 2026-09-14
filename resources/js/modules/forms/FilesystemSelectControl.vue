<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import type CraftFilesystemSelect from './filesystem-select';
  import './filesystem-select';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type FilesystemSelectProps = {
    options: ComboboxItem[];
    createUrl?: string;
    placeholder?: string;
    limit?: number;
    clearable?: boolean;
    requireOptionMatch?: boolean;
    showAllOnEmpty?: boolean;
    showSelectedHint?: boolean;
    dir?: string;
  };

  defineProps<{
    control: FormControlPayload<FilesystemSelectProps>;
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

    const value = (event.target as CraftFilesystemSelect).modelValue;

    if (value !== '__add__') {
      emit('update:value', String(value ?? ''), 'discrete');
    }
  }
</script>

<template>
  <craft-filesystem-select
    :name="editable ? inputName(control.path) : ''"
    .modelValue="String(value ?? '')"
    .options="control.props.options"
    .createUrl="control.props.createUrl ?? ''"
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
