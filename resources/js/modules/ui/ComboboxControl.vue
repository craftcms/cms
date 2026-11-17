<script setup lang="ts">
  import type ComboboxElement from '@craftcms/ui/components/combobox/combobox';
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import '@craftcms/ui/components/combobox/combobox';
  import {computed, shallowRef, watch} from 'vue';
  import type {UiChangeKind, UiControlPayload} from './types';
  import {handleCreateOption} from './combobox-create-option';
  import {inputName} from './runtime';

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

  function onInput(event: Event): void {
    const combobox = event.currentTarget as ComboboxElement;
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
      return;
    }

    emitValue(combobox, 'typing');
  }

  function emitValue(combobox: ComboboxElement, kind: UiChangeKind): void {
    const value = combobox.value;
    emit(
      'update:value',
      Array.isArray(value) ? value.map(String) : String(value ?? ''),
      kind
    );
  }
</script>

<template>
  <craft-combobox
    :name="editable ? inputName(control.path) : ''"
    .options="options"
    :placeholder="control.props.placeholder"
    .limit="control.props.limit ?? 150"
    .clearable="control.props.clearable ?? false"
    .requireOptionMatch="control.props.requireOptionMatch ?? false"
    .showAllOnEmpty="control.props.showAllOnEmpty ?? false"
    .showSelectedHint="control.props.showSelectedHint ?? false"
    :dir="control.props.dir"
    .required="editable && required"
    .readOnly="control.mode === 'readOnly'"
    .disabled="control.mode === 'disabled'"
    .invalid="invalid"
    .multiple="control.props.multiple ?? false"
    .value="modelValue"
    @input="onInput"
    @change="
      (event: Event) =>
        emitValue(event.currentTarget as ComboboxElement, 'discrete')
    "
  ></craft-combobox>
</template>
