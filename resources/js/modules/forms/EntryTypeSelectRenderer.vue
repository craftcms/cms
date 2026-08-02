<script setup lang="ts">
  import {useTemplateRef} from 'vue';
  import {useEventListener} from '@vueuse/core';

  defineOptions({inheritAttrs: false});

  defineProps<{
    selectHtml: string;
    modelValue?: unknown[];
    name?: string;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: unknown[]];
  }>();
  const container = useTemplateRef<HTMLElement>('container');

  function updateValue(): void {
    const inputs = container.value?.querySelectorAll<HTMLInputElement>(
      'craft-component-select > ul input[type="hidden"]'
    );

    emit(
      'update:modelValue',
      Array.from(inputs ?? [], ({value}) => JSON.parse(value))
    );
  }

  useEventListener(container, 'change', updateValue);
</script>

<template>
  <div ref="container" v-bind="$attrs" v-html="selectHtml"></div>
</template>
