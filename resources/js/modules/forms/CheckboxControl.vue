<script setup lang="ts">
  import CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type CheckboxControlProps = {
    label?: string;
    checkedValue?: string;
  };

  defineProps<{
    control: FormControlPayload<CheckboxControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{(event: 'update:value', value: boolean): void}>();

  const onModelValueChanged = ignoreModelValueInitialization((event) => {
    if (!(event.currentTarget instanceof CraftCheckbox)) {
      throw new TypeError('Expected a checkbox event target.');
    }

    emit('update:value', event.currentTarget.checked);
  });
</script>

<template>
  <craft-checkbox
    :name="editable ? inputName(control.path) : ''"
    :label="control.props.label ?? label"
    .checked="Boolean(value)"
    .choiceValue="String(control.props.checkedValue ?? '1')"
    :disabled="!editable"
    :required="editable && required"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-checkbox>
</template>
