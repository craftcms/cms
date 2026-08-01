<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import '@craftcms/ui/components/input-money/input-money';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: number | string | null];
  }>();
  const attrs = useAttrs();
  const hostAttributes = computed(() => ({...props.attributes, ...attrs}));
  const fractionDigits = computed(() =>
    typeof props.config.fractionDigits === 'number'
      ? props.config.fractionDigits
      : 2
  );
  const value = computed(() =>
    formatMinorUnits(props.binding?.value, fractionDigits.value)
  );
  const currency = computed(() => stringConfig('currency'));
  const placeholder = computed(() => stringConfig('placeholder'));

  function updateValue(event: Event): void {
    emit(
      'update:value',
      parseMinorUnits(
        (event.target as HTMLElementTagNameMap['craft-input-money']).value,
        fractionDigits.value
      )
    );
  }

  function stringConfig(name: string): string | undefined {
    const value = props.config[name];

    return typeof value === 'string' ? value : undefined;
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
  <craft-input-money
    v-bind="hostAttributes"
    :value="value"
    :currency="currency"
    :fraction-digits="fractionDigits"
    :placeholder="placeholder"
    @input="updateValue"
  ></craft-input-money>
</template>
