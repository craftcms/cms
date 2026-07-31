<script setup lang="ts">
  import {computed} from 'vue';
  import FormElementRenderer from './FormElementRenderer.vue';
  import type {FormDefinitionData, FormErrors, FormValues} from './types';

  const props = withDefaults(
    defineProps<{
      definition: FormDefinitionData;
      bindingScope: string;
      values: FormValues;
      errors: FormErrors;
      readOnly?: boolean;
    }>(),
    {readOnly: false}
  );

  const context = computed(() => ({
    bindingScope: props.bindingScope,
    values: props.values,
    errors: props.errors,
    readOnly: props.readOnly,
  }));
</script>

<template>
  <FormElementRenderer
    v-for="(element, index) in definition.elements"
    :key="element.name ?? `position:${index}`"
    :element="element"
    :context="context"
  />
</template>
