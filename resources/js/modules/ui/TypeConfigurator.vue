<script setup lang="ts">
  import {useTemplateRef} from 'vue';
  import TypePicker from '@/common/components/TypePicker.vue';
  import type {TypePickerOption} from '@/common/components/typePicker';
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
  <div class="type-configurator min-w-0">
    <div
      class="type-configurator__layout gap-1.5"
      :class="{'type-configurator__layout--actions': $slots.actions}"
    >
      <div class="type-configurator__type">
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
      </div>

      <div
        v-if="ui"
        class="type-configurator__fields flex flex-wrap items-start gap-1.5 min-w-0"
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

      <div v-if="$slots.actions" class="type-configurator__actions">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<style scoped>
  .type-configurator {
    container-type: inline-size;
  }

  .type-configurator__layout {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-areas: 'type fields';
    align-items: start;
  }

  .type-configurator__layout--actions {
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas: 'type fields actions';
  }

  .type-configurator__type {
    grid-area: type;
  }

  .type-configurator__fields {
    grid-area: fields;
  }

  .type-configurator__actions {
    grid-area: actions;
  }

  .type-configurator__fields :deep(craft-field) {
    margin-block: 0;
    min-inline-size: 0;
    flex: 1 1 8rem;
  }

  .type-configurator__fields :deep(craft-field:has(craft-select)) {
    flex: 0 0 auto;
  }

  @container (width < 22rem) {
    .type-configurator__layout {
      grid-template-columns: minmax(0, 1fr);
      grid-template-areas: 'type' 'fields';
    }

    .type-configurator__layout--actions {
      grid-template-columns: minmax(0, 1fr) auto;
      grid-template-areas: 'type actions' 'fields fields';
    }

    .type-configurator__fields :deep(craft-field),
    .type-configurator__fields :deep(craft-field:has(craft-select)) {
      flex: 1 1 100%;
    }

    .type-configurator__fields :deep(craft-select) {
      inline-size: 100%;
    }
  }
</style>
