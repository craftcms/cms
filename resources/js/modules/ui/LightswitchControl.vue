<script setup lang="ts">
  import CraftSwitch from '@craftcms/ui/components/switch/switch';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  type LightswitchControlProps = {
    indeterminate?: boolean;
    size?: 'small' | 'medium';
    checkedValue?: string;
    indeterminateValue?: string;
    onLabel?: string;
    offLabel?: string;
  };

  defineProps<{
    control: UiControlPayload<LightswitchControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{(event: 'update:value', value: boolean): void}>();

  function onChange(event: Event): void {
    if (!(event.currentTarget instanceof CraftSwitch)) {
      throw new TypeError('Expected a switch event target.');
    }

    emit('update:value', event.currentTarget.checked);
  }
</script>

<template>
  <craft-switch
    :name="editable ? inputName(control.path) : ''"
    .checked="Boolean(value)"
    .indeterminate="Boolean(control.props.indeterminate)"
    .value="String(control.props.checkedValue ?? '1')"
    .indeterminateValue="String(control.props.indeterminateValue ?? '-')"
    :size="control.props.size ?? 'medium'"
    .onLabel="control.props.onLabel ?? ''"
    .offLabel="control.props.offLabel ?? ''"
    .disabled="!editable"
    :required="editable && required"
    .invalid="invalid"
    @change="onChange"
  ></craft-switch>
</template>
