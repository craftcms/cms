<script setup lang="ts">
  import {nextTick, shallowRef, watch} from 'vue';
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import type CraftComboboxElement from '@craftcms/ui/components/combobox/combobox';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import {handleCreateOption} from './combobox-create-option';
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

  const props = defineProps<{
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
  const options = shallowRef(props.control.props.options);
  watch(
    () => props.control.props.options,
    (value) => (options.value = value)
  );
  let lastSelection: string | string[] = props.control.props.multiple
    ? Array.isArray(props.value)
      ? props.value.map(String)
      : []
    : String(props.value ?? '');
  watch(
    () => props.value,
    (value) => {
      if (Array.isArray(value)) {
        lastSelection = value.map(String);
      } else if (
        value == null ||
        value === '' ||
        options.value.some((item) =>
          (item.type === 'optgroup' ? item.options : [item]).some(
            (option) => option.value === String(value) && !option.data?.create
          )
        )
      ) {
        lastSelection = String(value ?? '');
      }
    }
  );

  function onModelValueChanged(
    event: CustomEvent,
    cancelModelUpdate: () => void
  ): void {
    if (event.detail?.initialize) {
      return;
    }

    const target = event.target as CraftComboboxElement;
    if (
      handleCreateOption(
        event,
        target,
        options.value,
        lastSelection,
        (updatedOptions, selected) => {
          options.value = updatedOptions;
          lastSelection = selected;
          void nextTick(() => {
            target.modelValue = selected;
            if (!Array.isArray(selected)) {
              const option = updatedOptions
                .flatMap((item) =>
                  item.type === 'optgroup' ? item.options : [item]
                )
                .find((item) => item.value === selected);
              const input = target.querySelector('input');
              if (input) input.value = option?.label ?? selected;
            }
          });
        }
      )
    ) {
      cancelModelUpdate();
      if (JSON.stringify(props.value) !== JSON.stringify(lastSelection)) {
        emit('update:value', lastSelection, 'discrete');
      }
      return;
    }

    const value = target.modelValue;
    if (JSON.stringify(value) === JSON.stringify(props.value)) {
      return;
    }

    if (event.detail?.changeSource !== 'input') {
      lastSelection = Array.isArray(value)
        ? value.map(String)
        : String(value ?? '');
    }

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
