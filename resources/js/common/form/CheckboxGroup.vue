<script setup lang="ts">
  import '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type {CheckboxSelectOption} from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type {CheckboxOption} from '@/common/types';

  /**
   * A `<craft-checkbox-select>` bound to `v-model` and `options`. A sortable
   * one lists only what's selected, with the rest in an “Add item” menu, and
   * reports its new order through `update:options`.
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
    (e: 'update:options', value: Array<CheckboxOption>): void;
  }>();

  const props = withDefaults(
    defineProps<{
      /** The options' input name, with or without a trailing `[]`. */
      name?: string;
      label?: string;
      disabled?: boolean;
      modelValue: Array<string>;
      options: Array<CheckboxOption>;
      allowSelectAll?: boolean;
      sortable?: boolean;
    }>(),
    {allowSelectAll: false, sortable: false}
  );

  // Forced-checked options (e.g. a disabled, always-on column) count as
  // selected whatever the model value says.
  const value = computed(() => [
    ...new Set([
      ...props.modelValue,
      ...props.options
        .filter((option) => option.checked)
        .map((option) => option.value),
    ]),
  ]);

  const elementOptions = computed<CheckboxSelectOption[]>(() =>
    props.options.map((option) => ({
      value: option.value,
      label: option.label,
      ...(option.labelHtml !== undefined ? {labelHtml: option.labelHtml} : {}),
      ...(option.info !== undefined ? {info: option.info} : {}),
      disabled: !!option.disabled,
      ...(option.checked ? {checked: true} : {}),
    }))
  );

  const baseName = computed(() => props.name?.replace(/\[\]$/, ''));

  /** Adding an option or moving one changes the order too. */
  function emitOrder(select: CraftCheckboxSelect): void {
    const byValue = new Map(
      props.options.map((option) => [option.value, option])
    );
    const order = select.options
      .map((option) => byValue.get(option.value))
      .filter((option): option is CheckboxOption => option !== undefined);

    if (order.some((option, index) => option !== props.options[index])) {
      emit('update:options', order);
    }
  }

  function onChange(event: Event): void {
    const select = event.currentTarget as CraftCheckboxSelect;
    emitOrder(select);
    emit('update:modelValue', select.value);
  }
</script>

<template>
  <craft-checkbox-select
    :name="baseName"
    :label="label"
    .disabled="disabled"
    .sortable="sortable"
    :presentation="sortable ? 'add-menu' : 'checkboxes'"
    :all-label="allowSelectAll ? t('All') : null"
    all-mode="each-value"
    .options="elementOptions"
    .value="value"
    @change="onChange"
  />
</template>
