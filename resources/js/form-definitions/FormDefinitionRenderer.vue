<script setup lang="ts">
  import {computed} from 'vue';
  import FormElementRenderer from './FormElementRenderer.vue';
  import type {
    FormDefinitionData,
    FormElementData,
    FormErrors,
    FormValues,
  } from './types';

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
  const renderableElements = computed(() => {
    assertOwnerlessRenderersAvailable(props.definition.elements);

    return props.definition.elements;
  });

  function assertOwnerlessRenderersAvailable(
    elements: FormElementData[]
  ): void {
    for (const element of elements) {
      if (
        element.type !== 'craft:field' &&
        !element.plugin &&
        !window.Cp.$components.resolve(`form-element:${element.type}`)
      ) {
        throw new Error(`Missing Form Element Renderer for ${element.type}.`);
      }

      assertOwnerlessRenderersAvailable(element.children ?? []);
    }
  }
</script>

<template>
  <FormElementRenderer
    v-for="(element, index) in renderableElements"
    :key="element.name ?? `position:${index}`"
    :element="element"
    :context="context"
  />
</template>
