<script setup lang="ts">
  import CraftInputMoney from '@craftcms/ui/components/input-money/input-money';
  import type {UiControlPayload, UiValue} from './types';
  import {inputName, isRecord} from './runtime';

  type MoneyValue = {value: string | null; locale: string};
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
    control: UiControlPayload<MoneyControlProps>;
    value: UiValue;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: MoneyValue, kind: 'typing'): void;
  }>();

  function moneyValue(): MoneyValue {
    if (isRecord(props.value)) {
      return {
        value: props.value.value == null ? null : String(props.value.value),
        locale: String(props.value.locale ?? props.control.props.locale),
      };
    }

    return {
      value: props.value == null ? null : String(props.value),
      locale: props.control.props.locale,
    };
  }

  const onInput = (event: Event) => {
    const input = event.currentTarget;
    if (!(input instanceof CraftInputMoney)) {
      throw new TypeError('Expected a money input event target.');
    }

    const amount = input.value;

    emit(
      'update:value',
      {
        // Report an empty amount the same way the server sends one. Reshaping
        // it into `''` reads as a real edit the moment the field is populated
        // on load, marking the form dirty before anyone has touched it.
        value: amount === '' ? null : amount,
        locale: moneyValue().locale,
      },
      'typing'
    );
  };
</script>

<template>
  <div>
    <craft-input-money
      :label="label"
      label-sr-only
      :name="editable ? `${inputName(control.path)}[value]` : ''"
      .value="moneyValue().value ?? ''"
      :min="control.props.min"
      :max="control.props.max"
      :step="control.props.step"
      .inputSize="control.props.size"
      :currency="control.props.currency"
      :locale="moneyValue().locale"
      .showCurrency="control.props.showCurrency"
      :required="editable && required"
      :readonly="control.mode === 'readOnly'"
      .disabled="control.mode === 'disabled'"
      :invalid="invalid"
      @input="onInput"
    ></craft-input-money>
    <input
      v-if="editable"
      type="hidden"
      :name="`${inputName(control.path)}[locale]`"
      :value="moneyValue().locale"
    />
  </div>
</template>
