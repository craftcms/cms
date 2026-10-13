<script setup lang="ts">
  import type ComboboxElement from '@craftcms/ui/components/combobox/combobox';
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import {computed, shallowRef, watch} from 'vue';
  import type {UiChangeKind, UiControlPayload} from './types';
  import {handleCreateOption} from './combobox-create-option';
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

  const props = defineProps<{
    control: UiControlPayload<ComboboxControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();

  const options = shallowRef(props.control.props.options);
  const modelValue = computed(() =>
    props.control.props.multiple
      ? Array.isArray(props.value)
        ? props.value.map(String)
        : []
      : String(props.value ?? '')
  );

  watch(
    () => props.control.props.options,
    (value) => (options.value = value)
  );

  const emit = defineEmits<{
    (event: 'update:value', value: string | string[], kind: UiChangeKind): void;
  }>();

  function onModelValueChanged(
    event: CustomEvent,
    cancelModelUpdate: () => void
  ): void {
    if (event.detail?.initialize) {
      return;
    }

    const combobox = event.target as ComboboxElement;
    if (
      handleCreateOption(
        event,
        combobox,
        options.value,
        modelValue.value,
        (createdOptions, value) => {
          options.value = createdOptions;
          emit('update:value', value, 'discrete');
        }
      )
    ) {
      cancelModelUpdate();
      return;
    }

    const value = combobox.modelValue;
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
    :options="options"
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
    :model-value="modelValue"
    @model-value-changed="onModelValueChanged"
  />
</template>
