<script setup lang="ts">
  import {computed} from 'vue';
  import '@craftcms/ui/components/input-money/input-money';

  const props = defineProps<{
    currency?: string;
    fractionDigits?: number;
    placeholder?: string;
    modelValue?: unknown;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: number | string | null];
  }>();
  const resolvedFractionDigits = computed(() => props.fractionDigits ?? 2);
  const value = computed(() =>
    formatMinorUnits(props.modelValue, resolvedFractionDigits.value)
  );

  function updateValue(event: Event): void {
    if (props.readonly) {
      return;
    }

    emit(
      'update:modelValue',
      parseMinorUnits(
        (event.target as HTMLElementTagNameMap['craft-input-money']).value,
        resolvedFractionDigits.value
      )
    );
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
    :value="value"
    :currency="currency"
    :fraction-digits="resolvedFractionDigits"
    :placeholder="placeholder"
    :readonly="readonly"
    @input="updateValue"
  ></craft-input-money>
</template>
