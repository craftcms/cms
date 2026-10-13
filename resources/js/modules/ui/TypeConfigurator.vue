<script setup lang="ts">
  import {useTemplateRef} from 'vue';
  import TypePicker, {
    type TypePickerOption,
  } from '@/common/components/TypePicker.vue';
  import UiRenderer from './UiRenderer.vue';
  import type {UiChange, UiPayload, UiValues} from './types';

  defineProps<{
    types: TypePickerOption[];
    selectedTypeLabel: string;
    typeLabel?: string;
    ui?: UiPayload | null;
    errors?: UiPayload['errors'];
    disabled?: boolean;
    uiDisabled?: boolean;
    refresh?: (
      values: UiPayload['values'],
      scope?: string[]
    ) => Promise<UiPayload>;
  }>();
  defineEmits<{
    select: [value: string];
    change: [change: UiChange, values: UiValues];
  }>();

  const uiRenderer = useTemplateRef('uiRenderer');

  defineExpose({
    currentValues: () => uiRenderer.value?.currentValues(),
    canSubmit: () => uiRenderer.value?.canSubmit() ?? true,
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
      v-if="ui"
      class="type-configurator__fields flex flex-wrap items-start gap-2 min-w-0 flex-1"
    >
      <UiRenderer
        ref="uiRenderer"
        :payload="ui"
        :errors="errors"
        :disabled="disabled || uiDisabled"
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
