<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ComboboxControlProps = {
    options: ComboboxItem[];
    multiple?: boolean;
    placeholder?: string;
    limit?: number;
    clearable?: boolean;
    requireOptionMatch?: boolean;
    showAllOnEmpty?: boolean;
    showSelectedHint?: boolean;
    dir?: string;
  };

  defineProps<{
    control: FormControlPayload<ComboboxControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: string | string[],
      kind: FormChangeKind
    ): void;
  }>();

  function onModelValueChanged(event: CustomEvent): void {
    if (event.detail?.initialize) {
      return;
    }

    const value = (event.target as HTMLElement & {modelValue?: unknown})
      .modelValue;
    emit(
      'update:value',
      Array.isArray(value) ? value.map(String) : String(value ?? ''),
      event.detail?.changeSource === 'input' ? 'typing' : 'discrete'
    );
  }
</script>

<template>
  <CraftCombobox
    :name="editable ? inputName(control.path) : ''"
    :label="label"
    :options="control.props.options"
    :placeholder="control.props.placeholder"
    :limit="control.props.limit"
    :clearable="control.props.clearable"
    :require-option-match="control.props.requireOptionMatch"
    :show-all-on-empty="control.props.showAllOnEmpty"
    :show-selected-hint="control.props.showSelectedHint"
    :dir="control.props.dir"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :validators="serverErrorValidators(invalid)"
    :multiple-choice="control.props.multiple ?? false"
    :model-value="
      control.props.multiple
        ? Array.isArray(value)
          ? value.map(String)
          : []
        : String(value ?? '')
    "
    @model-value-changed="onModelValueChanged"
  />
</template>
