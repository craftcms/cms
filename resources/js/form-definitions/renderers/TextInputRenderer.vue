<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: string];
  }>();

  const value = computed(() => String(props.binding?.value ?? ''));
  const placeholder = computed(() => {
    const placeholder = props.config.placeholder;

    return typeof placeholder === 'string' ? placeholder : undefined;
  });

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-input']).value
    );
  }
</script>

<template>
  <craft-input
    v-bind="attributes"
    type="text"
    :value="value"
    :placeholder="placeholder"
    @input="updateValue"
  ></craft-input>
</template>
