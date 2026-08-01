<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import CraftInputRenderer from './CraftInputRenderer.vue';

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

  function updateValue(value: string): void {
    emit('update:value', value === '' ? null : Number(value));
  }
</script>

<template>
  <CraftInputRenderer
    :attributes="attributes"
    type="number"
    :value="value"
    :min="numericProp('min')"
    :max="numericProp('max')"
    :step="numericProp('step')"
    @update:value="updateValue"
  />
</template>
