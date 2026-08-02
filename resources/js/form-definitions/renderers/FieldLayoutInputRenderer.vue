<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import type {
    AvailableFieldLayoutElement,
    FieldLayoutValue,
  } from '@craftcms/ui';
  import '@craftcms/ui/components/field-layout/field-layout';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: FieldLayoutValue];
  }>();
  const attrs = useAttrs();
  const hostProperties = computed(() => ({...props.attributes, ...attrs}));
  const value = computed<FieldLayoutValue>(() =>
    props.binding?.value &&
    typeof props.binding.value === 'object' &&
    !Array.isArray(props.binding.value)
      ? (props.binding.value as FieldLayoutValue)
      : {}
  );
  const availableElements = computed<AvailableFieldLayoutElement[]>(() =>
    Array.isArray(props.config.availableElements)
      ? (props.config.availableElements as AvailableFieldLayoutElement[])
      : []
  );

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-field-layout']).value
    );
  }
</script>

<template>
  <craft-field-layout
    v-bind="hostProperties"
    .value="value"
    .availableElements="availableElements"
    :with-generated-fields="config.withGeneratedFields === true"
    :readonly="binding?.readOnly ?? false"
    @input="updateValue"
  ></craft-field-layout>
</template>
