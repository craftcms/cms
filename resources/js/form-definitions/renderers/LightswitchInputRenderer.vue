<script setup lang="ts">
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/switch/switch';

  defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: boolean];
  }>();

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-switch']).checked
    );
  }
</script>

<template>
  <craft-switch
    v-bind="attributes"
    :checked="Boolean(binding?.value)"
    :disabled="binding?.readOnly"
    @change="updateValue"
  ></craft-switch>
</template>
