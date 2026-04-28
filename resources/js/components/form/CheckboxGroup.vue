<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import {computed} from 'vue';
  import type CraftCheckbox from '@craftcms/cp/components/checkbox/checkbox.ts.mjs';
  import type {CheckboxOption} from '@/types';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
  }>();
  const props = withDefaults(
    defineProps<{
      name?: string;
      label?: string;
      disabled?: boolean;
      modelValue: Array<string>;
      options: Array<CheckboxOption>;
      allowSelectAll?: boolean;
    }>(),
    {allowSelectAll: false}
  );

  function handleValueChange(event: CustomEvent) {
    const target = event.target as CraftCheckbox;
    emit('update:modelValue', target.modelValue);
  }

  const allOptionSelected = computed(() => {
    return !props.modelValue || props.modelValue.includes('*');
  });
</script>

<template>
  <craft-checkbox-group
    :name="name"
    :label="label"
    .modelValue="modelValue"
    @model-value-changed="handleValueChange"
    :disabled="disabled"
  >
    <template v-if="allowSelectAll">
      <craft-checkbox-indeterminate :label="t('All')">
        <template v-for="option in options" :key="option.value">
          <craft-checkbox .choiceValue="option.value">
            <label slot="label"
              ><slot name="label" :option="option">{{
                option.label
              }}</slot></label
            >
            <div v-if="option.info" slot="help-text" v-html="option.info"></div>
          </craft-checkbox>
        </template>
      </craft-checkbox-indeterminate>
    </template>
    <template v-else>
      <template v-for="option in options" :key="option.value">
        <craft-checkbox .choiceValue="option.value">
          <label slot="label"
            ><slot name="label" :option="option">{{
              option.label
            }}</slot></label
          >
          <div v-if="option.info" slot="help-text" v-html="option.info"></div>
        </craft-checkbox>
      </template>
    </template>
  </craft-checkbox-group>
</template>

<style scoped lang="scss"></style>
