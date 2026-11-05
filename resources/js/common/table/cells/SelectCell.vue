<script setup lang="ts">
  /** A table cell's native select, labeled for screen readers only. */
  import '@craftcms/ui/components/select/select';
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
    <craft-select
      class="cp-table-control__input"
      .modelValue="value"
      .disabled="disabled"
      :label="label"
      label-sr-only
      @model-value-changed="onChange"
    >
      <select slot="input">
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
        >
          {{ option.label }}
        </option>
      </select>
      <slot name="help-text" />
    </craft-select>
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
  }
</style>
