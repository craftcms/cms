<script setup lang="ts">
  import {computed} from 'vue';
  import FormElementRenderer from './FormElementRenderer.vue';
  import {isSharedContainer} from './form-element-types';
  import {reconciliationKey} from './reconciliation';
  import type {
    FormPayload,
    FormElementData,
    FormErrors,
    FormValues,
  } from './types';

  const props = withDefaults(
    defineProps<{
      form: FormPayload;
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
    assertOwnerlessRenderersAvailable(props.form.elements);

    return props.form.elements;
  });
  function assertOwnerlessRenderersAvailable(
    elements: FormElementData[]
  ): void {
    for (const element of elements) {
      if (
        !isSharedContainer(element.type) &&
        !element.plugin &&
        !window.Cp.$formElements.resolve(element.type)
      ) {
        throw new Error(`Missing Form Element Renderer for ${element.type}.`);
      }

      assertOwnerlessRenderersAvailable(element.children ?? []);
    }
  }
</script>

<template>
  <div data-form-root style="display: contents">
    <FormElementRenderer
      v-for="(element, index) in renderableElements"
      :key="reconciliationKey(element, index)"
      :element="element"
      :context="context"
    />
  </div>
</template>
