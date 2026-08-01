<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: number | null];
  }>();

  const value = computed(() => String(props.binding?.value ?? ''));

  function numericProp(name: string): number | undefined {
    const value = props.config[name];

    return typeof value === 'number' ? value : undefined;
  }

  function updateValue(event: Event): void {
    const value = (event.target as HTMLElementTagNameMap['craft-input']).value;

    emit('update:value', value === '' ? null : Number(value));
  }
</script>

<template>
  <craft-input
    v-bind="attributes"
    type="number"
    :value="value"
    :min="numericProp('min')"
    :max="numericProp('max')"
    :step="numericProp('step')"
    @input="updateValue"
  ></craft-input>
</template>
