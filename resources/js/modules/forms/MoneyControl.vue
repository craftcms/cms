<script setup lang="ts">
  import CraftInputMoney from '@craftcms/ui/components/input-money/input-money';
  import type {FormControlPayload} from './types';
  import {
    ignoreModelValueInitialization,
    inputName,
    serverErrorValidators,
  } from './runtime';

  type MoneyValue = {value: string; locale: string};
  type MoneyControlProps = {
    currency: string;
    locale: string;
    min?: number;
    max?: number;
    step?: number;
    size?: number;
    showCurrency: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<MoneyControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: MoneyValue, kind: 'typing'): void;
  }>();

  function moneyValue(): MoneyValue {
    return typeof props.value === 'object' && props.value !== null
      ? (props.value as MoneyValue)
      : {value: String(props.value ?? ''), locale: props.control.props.locale};
  }

  const onInput = ignoreModelValueInitialization((event) => {
    emit(
      'update:value',
      {
        value: String((event.target as CraftInputMoney).modelValue ?? ''),
        locale: moneyValue().locale,
      },
      'typing'
    );
  });
</script>

<template>
  <div>
    <craft-input-money
      :label="label"
      label-sr-only
      :name="editable ? `${inputName(control.path)}[value]` : undefined"
      .modelValue="moneyValue().value"
      :min="control.props.min"
      :max="control.props.max"
      :step="control.props.step"
      .inputSize="control.props.size"
      :currency="control.props.currency"
      :locale="moneyValue().locale"
      .showCurrency="control.props.showCurrency"
      :required="editable && required"
      :readonly="control.mode === 'readOnly'"
      :disabled="control.mode === 'disabled'"
      .validators="serverErrorValidators(invalid)"
      @model-value-changed="onInput"
    ></craft-input-money>
    <input
      v-if="editable"
      type="hidden"
      :name="`${inputName(control.path)}[locale]`"
      :value="moneyValue().locale"
    />
  </div>
</template>
