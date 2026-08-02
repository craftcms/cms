<script setup lang="ts">
  import {onMounted, useTemplateRef} from 'vue';
  import {useEventListener} from '@vueuse/core';

  defineOptions({inheritAttrs: false});

  type FieldLayoutValue = Record<string, unknown>;
  type SerializableElement<T> = HTMLElement & {serialize(): T};

  const props = withDefaults(
    defineProps<{
      name?: string;
      modelValue?: FieldLayoutValue;
      designerHtml: string;
      generatedFieldsHtml?: string;
      readonly?: boolean;
    }>(),
    {
      name: '',
      modelValue: () => ({}),
      generatedFieldsHtml: '',
      readonly: false,
    }
  );
  const emit = defineEmits<{
    'update:modelValue': [value: FieldLayoutValue];
  }>();
  const container = useTemplateRef<HTMLElement>('container');

  function updateValue(): void {
    const designer = container.value?.querySelector<
      SerializableElement<string>
    >('craft-field-layout-designer');

    if (!designer?.serialize) {
      return;
    }

    const value = JSON.parse(designer.serialize()) as FieldLayoutValue;
    const generatedFields = container.value?.querySelector<
      SerializableElement<Record<string, unknown>[]>
    >('craft-generated-fields-table');

    emit('update:modelValue', {
      ...value,
      ...(generatedFields?.serialize
        ? {generatedFields: generatedFields.serialize()}
        : {}),
    });
  }

  useEventListener(
    container,
    ['input', 'change', 'addRow', 'deleteRow', 'sortChange'],
    updateValue
  );

  onMounted(() => {
    const input = container.value?.querySelector<HTMLInputElement>(
      'craft-field-layout-designer [data-config-input]'
    );

    if (input) {
      input.name = props.name;
    }
  });
</script>

<template>
  <div ref="container" v-bind="$attrs" role="group">
    <div v-html="designerHtml"></div>
    <div v-if="generatedFieldsHtml" v-html="generatedFieldsHtml"></div>
  </div>
</template>
