<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type {CheckboxOption} from '@/common/types';
  import CheckboxGroupItem from '@/common/form/CheckboxGroupItem.vue';
  import SelectedOptionList from '@/common/form/SelectedOptionList.vue';
  import {ignoreModelValueInitialization} from '@/modules/ui/runtime';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
    (e: 'update:options', value: Array<CheckboxOption>): void;
  }>();

  const props = withDefaults(
    defineProps<{
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

  // The checkbox group derives each checkbox's checked state from its
  // modelValue, which would otherwise override a per-option `checked` flag. Fold
  // any forced-checked options (e.g. a disabled, always-on column) into the
  // value the group receives so they render checked.
  const groupModelValue = computed(() => {
    const forced = props.options
      .filter((option) => option.checked)
      .map((option) => option.value);
    return [...new Set([...props.modelValue, ...forced])];
  });

  // Lion announces a model value as its children register, before the group
  // has applied ours. Treating that as a change would emit an empty selection
  // over whatever the caller passed in.
  const handleValueChange = ignoreModelValueInitialization((event) => {
    const target = event.target;
    if (
      target instanceof HTMLElement &&
      'modelValue' in target &&
      Array.isArray(target.modelValue)
    ) {
      emit('update:modelValue', target.modelValue.map(String));
    }
  });
</script>

<template>
  <!-- Sortable selections list only what's selected, with the rest in an
       "Add item" menu. -->
  <SelectedOptionList
    v-if="sortable"
    :name="name"
    :label="label"
    :disabled="disabled"
    :model-value="modelValue"
    :options="options"
    @update:model-value="(value) => emit('update:modelValue', value)"
    @update:options="(value) => emit('update:options', value)"
  >
    <template #label="{option}">
      <slot name="label" :option="option">{{ option.label }}</slot>
    </template>
  </SelectedOptionList>

  <craft-checkbox-group
    v-else
    :name="name"
    :label="label"
    .modelValue="groupModelValue"
    @model-value-changed="handleValueChange"
    :disabled="disabled"
  >
    <craft-checkbox-indeterminate v-if="allowSelectAll" :label="t('All')">
      <div class="checkbox-group__items">
        <CheckboxGroupItem
          v-for="option in options"
          :key="option.value"
          :option="option"
        >
          <template #label="{option: slotOption}">
            <slot name="label" :option="slotOption">{{
              slotOption.label
            }}</slot>
          </template>
        </CheckboxGroupItem>
      </div>
    </craft-checkbox-indeterminate>

    <div v-else class="checkbox-group__items">
      <CheckboxGroupItem
        v-for="option in options"
        :key="option.value"
        :option="option"
      >
        <template #label="{option: slotOption}">
          <slot name="label" :option="slotOption">{{ slotOption.label }}</slot>
        </template>
      </CheckboxGroupItem>
    </div>
  </craft-checkbox-group>
</template>

<style scoped lang="scss">
  .checkbox-group__items {
    display: flex;
    flex-direction: column;
    gap: var(--c-checkbox-group-spacing);
  }
</style>
