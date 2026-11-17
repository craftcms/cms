<script setup lang="ts">
  import CraftInputHandle from '@craftcms/ui/components/input-handle/input-handle';
  import {toHandle} from '@craftcms/ui/utilities/string';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import type {UiChangeKind, UiControlPayload, UiPayload} from './types';
  import {inputName, valueAt} from './runtime';

  type HandleControlProps = {
    source?: string[];
    maxLength?: number;
    placeholder?: string;
  };

  const props = defineProps<{
    control: UiControlPayload<HandleControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
    values: UiPayload['values'];
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: UiChangeKind): void;
  }>();

  const generator = useInputGenerator(sourceValue, (source) => {
    if (props.editable && props.control.props.source) {
      emit('update:value', toHandle(source), 'typing');
    }
  });

  function onInput(event: Event): void {
    const input = event.currentTarget;
    if (!(input instanceof CraftInputHandle)) {
      throw new TypeError('Expected a handle input event target.');
    }

    emit('update:value', input.value, 'typing');
  }

  function sourceValue(): string {
    const source = props.control.props.source;

    if (!source) {
      return '';
    }

    return String(
      valueAt(props.values, [...props.control.path.slice(0, -1), ...source]) ??
        ''
    );
  }
</script>

<template>
  <craft-input-handle
    :name="editable ? inputName(control.path) : ''"
    .value="String(value ?? '')"
    :maxlength="control.props.maxLength"
    :placeholder="control.props.placeholder"
    .autocomplete="'off'"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    .disabled="control.mode === 'disabled'"
    :invalid="invalid"
    @input="onInput"
    @change="generator.markDirty()"
  ></craft-input-handle>
</template>
