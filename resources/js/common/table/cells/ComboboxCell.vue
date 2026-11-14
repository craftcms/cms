<script setup lang="ts">
  /**
   * A table cell's combobox, labeled for screen readers only: a select whose options
   * are filtered by typing, for a list too long to scan, each with a hint beside its
   * label. An option for no value, such as “Please select”, is the field's placeholder,
   * and clearing the field chooses it, so its label isn't typed over.
   */
  import '@craftcms/ui/components/combobox/combobox';
  import type {ComboboxOption} from '@craftcms/ui/components/combobox/combobox';
  import {computed} from 'vue';
  import type {SelectCellOption} from './types';

  const {options} = defineProps<{
    value: string;
    options: SelectCellOption[];
    label: string;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{change: [value: string]}>();

  const emptyOption = computed(() =>
    options.find((option) => option.value === '')
  );

  const comboboxOptions = computed<ComboboxOption[]>(() =>
    options
      .filter((option) => option.value !== '')
      .map((option) => ({
        label: option.label,
        value: option.value,
        disabled: Boolean(option.disabled),
        data: option.hint ? {hint: option.hint} : null,
      }))
  );

  /** `change` only fires once an option is chosen or the field is left, not as it's typed in. */
  function onChange(event: Event): void {
    const value = (event.currentTarget as {modelValue?: unknown}).modelValue;
    emit('change', value == null ? '' : String(value));
  }
</script>

<template>
  <div class="cp-table-control">
    <craft-combobox
      class="cp-table-control__input"
      .modelValue="value"
      .options="comboboxOptions"
      .disabled="disabled"
      :label="label"
      label-sr-only
      show-all-on-empty
      :placeholder="emptyOption?.label ?? ''"
      :clearable="emptyOption !== undefined"
      @change="onChange"
    ></craft-combobox>
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
