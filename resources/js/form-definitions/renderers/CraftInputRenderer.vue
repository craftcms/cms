<script setup lang="ts">
  import '@craftcms/ui/components/input/input';
  import {computed, useAttrs} from 'vue';
  import type {JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    attributes: Record<string, JsonValue>;
    type: string;
    value: string;
  }>();

  const emit = defineEmits<{
    'update:value': [value: string];
  }>();
  const attrs = useAttrs();
  const hostAttributes = computed(() => ({...props.attributes, ...attrs}));

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-input']).value
    );
  }
</script>

<template>
  <craft-input
    v-bind="hostAttributes"
    :type="type"
    :value="value"
    @input="updateValue"
  ></craft-input>
</template>
