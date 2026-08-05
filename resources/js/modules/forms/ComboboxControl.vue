<script setup lang="ts">
  import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type ComboboxControlProps = {
    options: ComboboxItem[];
    placeholder?: string;
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
    (event: 'update:value', value: string, kind: 'typing'): void;
  }>();

  function onValueChanged(value: string | number | boolean | undefined): void {
    emit('update:value', String(value ?? ''), 'typing');
  }
</script>

<template>
  <CraftCombobox
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : ''"
    :model-value="String(value ?? '')"
    :options="control.props.options"
    :placeholder="control.props.placeholder"
    :required="editable && required"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :validators="serverErrorValidators(invalid)"
    @update:model-value="onValueChanged"
  />
</template>
