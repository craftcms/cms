<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';

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
      ? props.binding.value.slice(0, 5)
      : ''
  );

  function updateValue(event: Event): void {
    const value = (event.target as HTMLElementTagNameMap['craft-input']).value;

    emit('update:value', value || null);
  }
</script>

<template>
  <craft-input
    v-bind="attributes"
    type="time"
    :value="value"
    @input="updateValue"
  ></craft-input>
</template>
