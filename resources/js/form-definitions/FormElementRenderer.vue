<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import {
    htmlInputName,
    inputId,
    scopedPath,
    setValueAt,
    valueAt,
  } from './binding';
  import type {
    FieldContext,
    FormElementBinding,
    FormElementData,
    JsonValue,
    RenderContext,
  } from './types';

  const props = defineProps<{
    element: FormElementData;
    context: RenderContext;
    fieldContext?: FieldContext;
  }>();

  const renderer = computed(() => {
    if (props.element.type === 'craft:field') {
      return null;
    }

    const component = window.Cp.$components.resolve(
      `form-element:${props.element.type}`
    );

    if (!component) {
      throw new Error(
        `Missing Form Element Renderer for ${props.element.type}.`
      );
    }

    return component;
  });

  const inputChild = computed(() => props.element.children?.[0]);
  const inputPath = computed(() => {
    const name = inputChild.value?.name;

    return name ? scopedPath(props.context.bindingScope, name) : undefined;
  });
  const fieldInputId = computed(() =>
    inputPath.value ? inputId(inputPath.value) : ''
  );
  const label = computed(() => stringProp('label'));
  const instructions = computed(() => stringProp('instructions'));
  const fieldReadOnly = computed(
    () => props.context.readOnly || props.element.props?.readOnly === true
  );
  const fieldErrors = computed(() => {
    if (!inputPath.value) {
      return [];
    }

    const errors = props.context.errors[inputPath.value];

    return typeof errors === 'string' ? [errors] : (errors ?? []);
  });
  const labelId = computed(() => `${fieldInputId.value}-label`);
  const instructionsId = computed(() => `${fieldInputId.value}-instructions`);
  const errorsId = computed(() => `${fieldInputId.value}-errors`);
  const describedBy = computed(
    () =>
      [
        instructions.value ? instructionsId.value : undefined,
        fieldErrors.value.length ? errorsId.value : undefined,
      ]
        .filter((id): id is string => Boolean(id))
        .join(' ') || undefined
  );
  const childFieldContext = computed<FieldContext>(() => ({
    inputId: fieldInputId.value,
    labelledBy: label.value ? labelId.value : undefined,
    describedBy: describedBy.value,
    readOnly: fieldReadOnly.value,
  }));

  const binding = computed<FormElementBinding | undefined>(() => {
    if (!props.element.name) {
      return undefined;
    }

    const path = scopedPath(props.context.bindingScope, props.element.name);

    return {
      name: props.element.name,
      value: valueAt(props.context.values, path),
      readOnly:
        props.context.readOnly || (props.fieldContext?.readOnly ?? false),
    };
  });

  const attributes = computed<Record<string, JsonValue>>(() => {
    if (!binding.value) {
      return props.element.attributes ?? {};
    }

    const path = scopedPath(props.context.bindingScope, binding.value.name);
    const attributes: Record<string, JsonValue> = {
      ...props.element.attributes,
      id: props.fieldContext?.inputId ?? inputId(path),
      name: htmlInputName(path),
      readonly: binding.value.readOnly,
    };

    if (props.fieldContext?.labelledBy) {
      attributes['aria-labelledby'] = props.fieldContext.labelledBy;
    }

    if (props.fieldContext?.describedBy) {
      attributes['aria-describedby'] = props.fieldContext.describedBy;
    }

    return attributes;
  });

  const width = computed(() =>
    props.element.width ? `${props.element.width}%` : undefined
  );

  function stringProp(name: string): string | undefined {
    const value = props.element.props?.[name];

    return typeof value === 'string' ? value : undefined;
  }

  function updateValue(value: unknown): void {
    if (!props.element.name) {
      return;
    }

    setValueAt(
      props.context.values,
      scopedPath(props.context.bindingScope, props.element.name),
      value
    );
  }

  function reconciliationKey(element: FormElementData, index: number): string {
    return element.name ?? `position:${index}`;
  }
</script>

<template>
  <div
    v-if="element.type === 'craft:field'"
    data-form-element="craft:field"
    :style="{width}"
  >
    <label v-if="label" :id="labelId" :for="fieldInputId">{{ label }}</label>
    <p v-if="instructions" :id="instructionsId" data-form-element-instructions>
      {{ instructions }}
    </p>
    <FormElementRenderer
      v-for="(child, index) in element.children"
      :key="reconciliationKey(child, index)"
      :element="child"
      :context="context"
      :field-context="childFieldContext"
    />
    <ul
      v-if="fieldErrors.length"
      :id="errorsId"
      data-form-element-errors
      :aria-label="t('Validation errors')"
    >
      <li v-for="error in fieldErrors" :key="error">{{ error }}</li>
    </ul>
  </div>

  <component
    :is="renderer"
    v-else-if="renderer"
    :config="(element.props ?? {}) as Record<string, JsonValue>"
    :attributes="attributes"
    :binding="binding"
    :style="{width}"
    @update:value="updateValue"
  />
</template>
