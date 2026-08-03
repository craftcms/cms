<script setup lang="ts">
  import {computed} from 'vue';
  import FormElementRenderer from './FormElementRenderer.vue';
  import {reconciliationKey} from './reconciliation';
  import type {FormPayload, FormErrors, FormValues} from './types';

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
</script>

<template>
  <div data-form-root style="display: contents">
    <FormElementRenderer
      v-for="(element, index) in form.elements"
      :key="reconciliationKey(element, index)"
      :element="element"
      :context="context"
    />
  </div>
</template>
