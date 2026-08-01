<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/switch/switch';

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: boolean];
  }>();

  const label = computed(() => stringConfig('label'));
  const onLabel = computed(() => stringConfig('onLabel'));
  const offLabel = computed(() => stringConfig('offLabel'));
  const size = computed(() => {
    const size = stringConfig('size');

    return size === 'small' || size === 'medium' ? size : undefined;
  });

  function stringConfig(name: string): string | undefined {
    const value = props.config[name];

    return typeof value === 'string' ? value : undefined;
  }

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
    :label="label"
    .onLabel="onLabel"
    .offLabel="offLabel"
    .size="size"
    @change="updateValue"
  ></craft-switch>
</template>
