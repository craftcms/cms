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
    'update:value': [value: string];
  }>();

  const value = computed(() => String(props.binding?.value ?? ''));
  const placeholder = computed(() => {
    const placeholder = props.config.placeholder;

    return typeof placeholder === 'string' ? placeholder : undefined;
  });
</script>

<template>
  <CraftInputRenderer
    :attributes="attributes"
    type="text"
    :value="value"
    :placeholder="placeholder"
    @update:value="emit('update:value', $event)"
  />
</template>
