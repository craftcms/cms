<script setup lang="ts">
  import {useTemplateRef} from 'vue';
  import TypePicker, {
    type TypePickerOption,
  } from '@/common/components/TypePicker.vue';
  import FormRenderer from './FormRenderer.vue';
  import type {FormChange, FormPayload, FormValues} from './types';

  defineProps<{
    types: TypePickerOption[];
    selectedTypeLabel: string;
    typeLabel?: string;
    form?: FormPayload | null;
    errors?: FormPayload['errors'];
    disabled?: boolean;
    formDisabled?: boolean;
    refresh?: (
      values: FormPayload['values'],
      scope?: string[]
    ) => Promise<FormPayload>;
  }>();
  defineEmits<{
    select: [value: string];
    change: [change: FormChange, values: FormValues];
  }>();

  const formRenderer = useTemplateRef('formRenderer');

  defineExpose({
    currentValues: () => formRenderer.value?.currentValues(),
    canSubmit: () => formRenderer.value?.canSubmit() ?? true,
  });
</script>

<template>
  <div class="type-configurator flex flex-wrap items-start gap-2 min-w-0">
    <craft-field v-if="typeLabel" :label="typeLabel" fieldset width="auto">
      <TypePicker
        slot="input"
        :types="types"
        :label="selectedTypeLabel"
        :disabled="disabled"
        @select="$emit('select', $event)"
      />
    </craft-field>
    <TypePicker
      v-else
      :types="types"
      :label="selectedTypeLabel"
      :disabled="disabled"
      @select="$emit('select', $event)"
    />

    <div
      v-if="form"
      class="type-configurator__fields flex flex-wrap items-start gap-2 min-w-0 flex-1"
    >
      <FormRenderer
        ref="formRenderer"
        :payload="form"
        :errors="errors"
        :disabled="disabled || formDisabled"
        :refresh="refresh"
        @change="(change, values) => $emit('change', change, values)"
      />
    </div>
  </div>
</template>

<style scoped>
  .type-configurator {
    container-type: inline-size;
  }

  .type-configurator__fields :deep(craft-field) {
    margin-block: 0;
    min-inline-size: 0;
    flex: 1 1 0;
  }

  .type-configurator__fields :deep(craft-field:has(craft-select)) {
    flex: 0 0 auto;
  }

  @container (width < 22rem) {
    .type-configurator__fields {
      flex-basis: 100%;
    }
  }
</style>
