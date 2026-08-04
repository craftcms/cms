<script setup lang="ts">
  import CraftSwitch from '@craftcms/ui/components/switch/switch';
  import type {FormControlPayload} from './types';
  import {inputName, serverErrorValidators} from './runtime';

  type LightswitchControlProps = {
    indeterminate?: boolean;
    size?: 'small' | 'medium';
    checkedValue?: string;
    indeterminateValue?: string;
    onLabel?: string;
    offLabel?: string;
  };

  defineProps<{
    control: FormControlPayload<LightswitchControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{(event: 'update:value', value: boolean): void}>();

  function onModelValueChanged(event: Event): void {
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }

    emit('update:value', (event.target as CraftSwitch).checked);
  }
</script>

<template>
  <craft-switch
    :label="label"
    label-sr-only
    :name="editable ? inputName(control.path) : undefined"
    .checked="Boolean(value)"
    .indeterminate="Boolean(control.props.indeterminate)"
    .checkedValue="String(control.props.checkedValue ?? '1')"
    .indeterminateValue="String(control.props.indeterminateValue ?? '-')"
    :size="control.props.size ?? 'medium'"
    .onLabel="control.props.onLabel ?? ''"
    .offLabel="control.props.offLabel ?? ''"
    :disabled="!editable"
    :required="editable && required"
    .validators="serverErrorValidators(invalid)"
    @model-value-changed="onModelValueChanged"
  ></craft-switch>
</template>
