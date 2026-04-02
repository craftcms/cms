<script setup lang="ts">
  import ComponentSelect, {
    type ComponentSelectEmits,
    type ComponentSelectProps,
  } from '@/components/form/ComponentSelect/ComponentSelect.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';

  export interface EntryTypeSelectProps extends ComponentSelectProps {
    allowOverrides?: boolean;
  }

  const emit = defineEmits<ComponentSelectEmits>();
  const props = withDefaults(defineProps<EntryTypeSelectProps>(), {
    modelValue: false,
    id: () => `element-type-select`,
    options: () => [],
    limit: null,
    showHandles: false,
    showIndicators: false,
    showDescription: false,
    sortable: true,
    showActionMenus: true,
    hyperLinks: false,
    createAction: null,
    disabled: false,
    registerJs: true,
    renderDefaultInput: true,
    selectable: true,

    // Entry types specific
    allowOverrides: false,
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
