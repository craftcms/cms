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
    'update:value': [value: string | null];
  }>();

  const value = computed(() =>
    typeof props.binding?.value === 'string'
      ? props.binding.value.slice(0, 10)
      : ''
  );

  function updateValue(value: string): void {
    emit('update:value', value || null);
  }
</script>

<template>
  <CraftInputRenderer
    :attributes="attributes"
    type="date"
    :value="value"
    @update:value="updateValue"
  />
</template>
