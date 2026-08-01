<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: number | string | null];
  }>();

  const fractionDigits = computed(() =>
    typeof props.config.fractionDigits === 'number'
      ? props.config.fractionDigits
      : 2
  );
  const value = computed(() =>
    formatMinorUnits(props.binding?.value, fractionDigits.value)
  );
  const step = computed(() => 10 ** -fractionDigits.value);

  function updateValue(event: Event): void {
    const value = (event.target as HTMLElementTagNameMap['craft-input']).value;

    emit('update:value', parseMinorUnits(value, fractionDigits.value));
  }

  function formatMinorUnits(value: unknown, digits: number): string {
    if (value === null || value === undefined || value === '') {
      return '';
    }

    const raw = String(value);
    const sign = raw.startsWith('-') ? '-' : '';
    const amount = raw.replace(/^-/, '').padStart(digits + 1, '0');

    if (digits === 0) {
      return `${sign}${amount}`;
    }

    return `${sign}${amount.slice(0, -digits)}.${amount.slice(-digits)}`;
  }

  function parseMinorUnits(
    value: string,
    digits: number
  ): number | string | null {
    if (value === '') {
      return null;
    }

    const sign = value.startsWith('-') ? '-' : '';
    const [whole = '0', fraction = ''] = value.replace(/^-/, '').split('.');
    const amount =
      `${whole || '0'}${fraction.padEnd(digits, '0').slice(0, digits)}`.replace(
        /^0+(?=\d)/,
        ''
      );
    const minorUnits = `${sign}${amount}`;
    const numericValue = Number(minorUnits);

    return Number.isSafeInteger(numericValue) ? numericValue : minorUnits;
  }
</script>

<template>
  <craft-input
    v-bind="attributes"
    type="number"
    :value="value"
    :step="step"
    @input="updateValue"
  ></craft-input>
</template>
