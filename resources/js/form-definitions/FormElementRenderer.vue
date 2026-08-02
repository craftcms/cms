<script setup lang="ts">
  import {computed, onErrorCaptured, ref, watch} from 'vue';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';
  import '@craftcms/ui/components/indicator/indicator';
  import '@craftcms/ui/components/tab/tab';
  import '@craftcms/ui/components/tabs/tabs';
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
    FormElementData,
    JsonValue,
    RenderContext,
  } from './types';
  import {isSharedContainer} from './form-element-types';
  import {reconciliationKey} from './reconciliation';
  import {evaluateVisibilityCondition} from './visibility';

  const props = defineProps<{
    element: FormElementData;
    context: RenderContext;
    fieldContext?: FieldContext;
    hostSlot?: string;
  }>();

  const renderer = computed(() => {
    if (isSharedContainer(props.element.type)) {
      return null;
    }

    const component = window.Cp.$components.resolve(
      `form-element:${props.element.type}`
    );

    if (!component && !props.element.plugin) {
      throw new Error(
        `Missing Form Element Renderer for ${props.element.type}.`
      );
    }

    return component;
  });
  const rendererFailure = ref<Error>();
  const missingRenderer = computed(
    () => !isSharedContainer(props.element.type) && !renderer.value
  );
  const missingRendererMessage = computed(() => {
    const plugin = props.element.plugin!;

    return t(
      'Missing Form Element Renderer for {type} from {name} ({handle}, {packageName}). Ensure the plugin is enabled and its control-panel assets are available.',
      {
        type: props.element.type,
        name: plugin.name,
        handle: plugin.handle,
        packageName: plugin.packageName,
      }
    );
  });
  const failedRendererMessage = computed(() => {
    const plugin = props.element.plugin;
    const context = plugin
      ? ` ${t('for {name} ({handle}, {packageName})', {
          name: plugin.name,
          handle: plugin.handle,
          packageName: plugin.packageName,
        })}`
      : '';

    return t(
      'Form Element Renderer {type} failed{context}: {message} Check the renderer implementation.',
      {
        type: props.element.type,
        context,
        message: rendererFailure.value?.message,
      }
    );
  });

  onErrorCaptured((error) => {
    if (props.element.type === 'craft:field' || !renderer.value) {
      return;
    }

    rendererFailure.value =
      error instanceof Error ? error : new Error(String(error));

    return false;
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
  const tip = computed(() => stringProp('tip'));
  const warning = computed(() => stringProp('warning'));
  const required = computed(() => props.element.props?.required === true);
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
  const childFieldContext = computed<FieldContext>(() => ({
    inputId: fieldInputId.value,
    readOnly: fieldReadOnly.value,
    required: required.value,
  }));

  const bindingPath = computed(() =>
    props.element.name
      ? scopedPath(props.context.bindingScope, props.element.name)
      : undefined
  );
  const readOnly = computed(
    () => props.context.readOnly || (props.fieldContext?.readOnly ?? false)
  );

  const attributes = computed<Record<string, JsonValue>>(() => {
    const elementAttributes = htmlAttributes(props.element.attributes);

    if (!bindingPath.value) {
      return elementAttributes;
    }

    const attributes: Record<string, JsonValue> = {
      ...elementAttributes,
      id: props.fieldContext?.inputId ?? inputId(bindingPath.value),
      name: htmlInputName(bindingPath.value),
      readonly: readOnly.value,
    };

    if (props.fieldContext?.required) {
      attributes.required = true;
      attributes['aria-required'] = 'true';
    }

    return attributes;
  });
  const rendererProps = computed(() => ({
    ...props.element.props,
    ...attributes.value,
    ...(bindingPath.value
      ? {modelValue: valueAt(props.context.values, bindingPath.value)}
      : {}),
    readonly: readOnly.value,
  }));

  const width = computed(() => elementWidth(props.element));
  const visible = computed(() => elementVisible(props.element));
  const tabs = computed(() => props.element.children ?? []);
  const visibleTabs = computed(() => tabs.value.filter(elementVisible));
  const selectedTabKey = ref<string>();
  const selectedTabIndex = computed(() =>
    Math.max(
      0,
      tabs.value.findIndex((tab) => tab.key === selectedTabKey.value)
    )
  );

  watch(
    visibleTabs,
    (tabs) => {
      if (!tabs.some((tab) => tab.key === selectedTabKey.value)) {
        selectedTabKey.value = tabs[0]?.key ?? undefined;
      }
    },
    {immediate: true}
  );

  function stringProp(name: string): string | undefined {
    const value = props.element.props?.[name];

    return typeof value === 'string' ? value : undefined;
  }

  function elementWidth(element: FormElementData): string | undefined {
    return element.width ? `${element.width}%` : undefined;
  }

  function elementVisible(element: FormElementData): boolean {
    return element.visibleWhen
      ? evaluateVisibilityCondition(
          element.visibleWhen,
          props.context.values,
          props.context.bindingScope
        )
      : true;
  }

  function htmlAttributes(
    attributes: FormElementData['attributes']
  ): Record<string, JsonValue> {
    const normalized: Record<string, JsonValue> = {};
    const groupedAttributes = [
      'aria',
      'data',
      'data-hx',
      'data-ng',
      'hx',
      'ng',
    ];

    for (const [name, value] of Object.entries(attributes ?? {})) {
      if (
        groupedAttributes.includes(name) &&
        typeof value === 'object' &&
        value !== null &&
        !Array.isArray(value)
      ) {
        for (const [nestedName, nestedValue] of Object.entries(value)) {
          normalized[`${name}-${nestedName}`] = nestedValue;
        }

        continue;
      }

      normalized[name] = value;
    }

    return normalized;
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

  function selectTab(event: Event): void {
    const index = (
      event.currentTarget as HTMLElement & {
        selectedIndex: number;
      }
    ).selectedIndex;

    selectedTabKey.value = tabs.value[index]?.key ?? undefined;
  }
</script>

<template>
  <craft-field
    v-if="element.type === 'craft:field'"
    v-show="visible"
    :slot="hostSlot"
    data-form-element="craft:field"
    :style="{width}"
    :label="label"
    :required="required"
    :readonly="fieldReadOnly"
    :has-errors="fieldErrors.length > 0"
  >
    <p v-if="instructions" slot="help-text" data-form-element-instructions>
      {{ instructions }}
    </p>
    <p v-if="tip" slot="tip" data-form-element-tip>{{ tip }}</p>
    <p v-if="warning" slot="warning" data-form-element-warning>
      {{ warning }}
    </p>
    <FormElementRenderer
      v-for="(child, index) in element.children"
      :key="reconciliationKey(child, index)"
      :element="child"
      :context="context"
      :field-context="childFieldContext"
      host-slot="input"
    />
    <ul
      v-if="fieldErrors.length"
      slot="feedback"
      data-form-element-errors
      :aria-label="t('Validation errors')"
    >
      <li v-for="error in fieldErrors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>

  <craft-field-group
    v-else-if="element.type === 'craft:group' || element.type === 'craft:tab'"
    v-bind="attributes"
    v-show="visible"
    :slot="hostSlot"
    :data-form-element="element.type"
    :style="{width}"
  >
    <FormElementRenderer
      v-for="(child, index) in element.children"
      :key="reconciliationKey(child, index)"
      :element="child"
      :context="context"
    />
  </craft-field-group>

  <craft-tabs
    v-else-if="element.type === 'craft:tabs'"
    v-bind="attributes"
    v-show="visible"
    :slot="hostSlot"
    data-form-element="craft:tabs"
    :style="{width}"
    :selected-index="selectedTabIndex"
    @selected-changed="selectTab"
  >
    <craft-tab
      v-for="tab in tabs"
      v-bind="htmlAttributes(tab.attributes)"
      v-show="visibleTabs.length > 1 && elementVisible(tab)"
      :key="`tab:${tab.key}`"
      slot="tab"
    >
      {{ tab.props?.label }}
      <craft-indicator
        v-if="tab.props?.hasErrors === true"
        fill="danger"
        :label="t('Contains errors')"
        data-form-tab-errors
      />
    </craft-tab>
    <craft-field-group
      v-for="tab in tabs"
      v-show="elementVisible(tab)"
      :key="`panel:${tab.key}`"
      slot="panel"
      :data-form-tab-panel="tab.key"
      :style="{width: elementWidth(tab)}"
    >
      <FormElementRenderer
        v-for="(child, index) in tab.children"
        :key="reconciliationKey(child, index)"
        :element="child"
        :context="context"
      />
    </craft-field-group>
  </craft-tabs>

  <template v-else>
    <component
      :is="renderer"
      v-if="renderer && hostSlot && !rendererFailure"
      v-show="visible"
      :slot="hostSlot"
      :style="{width}"
      v-bind="rendererProps"
      @update:model-value="updateValue"
    >
      <FormElementRenderer
        v-for="(child, index) in element.children"
        :key="reconciliationKey(child, index)"
        :element="child"
        :context="context"
      />
    </component>
    <div v-else v-show="visible" :slot="hostSlot" :style="{width}">
      <div v-if="missingRenderer" data-form-element-missing-renderer>
        {{ missingRendererMessage }}
      </div>
      <div v-else-if="rendererFailure" data-form-element-failed-renderer>
        {{ failedRendererMessage }}
      </div>
      <component
        :is="renderer"
        v-else-if="renderer"
        v-bind="rendererProps"
        @update:model-value="updateValue"
      >
        <FormElementRenderer
          v-for="(child, index) in element.children"
          :key="reconciliationKey(child, index)"
          :element="child"
          :context="context"
        />
      </component>
    </div>
  </template>
</template>
