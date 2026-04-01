<script setup lang="ts">
  import ComponentSelect, {
    type ComponentSelectEmits,
    type ComponentSelectProps,
  } from '@/components/form/ComponentSelect.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';

  export interface EntryTypeSelectProps extends ComponentSelectProps {
    allowOverrides?: boolean;
  }

  const emit = defineEmits<ComponentSelectEmits>();
  const props = withDefaults(defineProps<EntryTypeSelectProps>(), {
    allowOverrides: false,
    showIndicators: true,
  });
</script>

<template>
  <ComponentSelect
    v-bind="$props"
    :show-indicators="showIndicators"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template #component="{component, remove}">
      <DynamicHtmlRenderer :html="component.chipHtml"></DynamicHtmlRenderer>
    </template>
  </ComponentSelect>
</template>

<style scoped lang="scss"></style>
