<script setup lang="ts">
  import type {CheckboxOption} from '@/common/types';

  defineProps<{
    option: CheckboxOption;
  }>();
</script>

<template>
  <craft-checkbox
    .choiceValue="option.value"
    .checked="option.checked"
    .disabled="option.disabled"
  >
    <label slot="label">
      <slot name="label" :option="option">{{ option.label }}</slot>
    </label>
    <div v-if="option.info" slot="help-text" v-html="option.info"></div>
  </craft-checkbox>
</template>
