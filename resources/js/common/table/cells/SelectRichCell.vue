<script setup lang="ts">
  /**
   * A table cell's rich select, labeled for screen readers only. Unlike `SelectCell`,
   * each option can show a hint beside its label, at the cost of the platform picker.
   */
  import '@craftcms/ui/components/select-rich/select-rich';
  import {isInitialization, type SelectCellOption} from './types';

  defineProps<{
    value: string;
    options: SelectCellOption[];
    label: string;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{change: [value: string]}>();

  function onChange(event: Event): void {
    if (isInitialization(event)) {
      return;
    }

    const value = (event.currentTarget as {modelValue?: unknown}).modelValue;
    emit('change', value == null ? '' : String(value));
  }
</script>

<template>
  <div class="cp-table-control">
    <craft-select-rich
      class="cp-table-control__input"
      .modelValue="value"
      .disabled="disabled"
      :label="label"
      label-sr-only
      @model-value-changed="onChange"
    >
      <craft-option
        v-for="option in options"
        :key="option.value"
        .choiceValue="option.value"
        .hint="option.hint ?? null"
        .disabled="Boolean(option.disabled)"
      >
        {{ option.label }}
      </craft-option>
      <slot name="help-text" />
    </craft-select-rich>
  </div>
</template>

<style scoped>
  .cp-table-control {
    display: flex;
    align-items: center;
    min-height: var(--c-size-control-md);
  }

  .cp-table-control__input {
    flex: 1;
    min-width: 0;
  }
</style>
