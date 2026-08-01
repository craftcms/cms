<script setup lang="ts">
  import {computed, onErrorCaptured, ref, useId, watch} from 'vue';
  import '@craftcms/ui/components/indicator/indicator';
  import '@craftcms/ui/components/tab/tab';
  import {t} from '@craftcms/ui/utilities/translate';
  import type {FormElementBinding} from '@craftcms/ui';
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
  const labelId = computed(() => `${fieldInputId.value}-label`);
  const instructionsId = computed(() => `${fieldInputId.value}-instructions`);
  const errorsId = computed(() => `${fieldInputId.value}-errors`);
  const tipId = computed(() => `${fieldInputId.value}-tip`);
  const warningId = computed(() => `${fieldInputId.value}-warning`);
  const describedBy = computed(
    () =>
      [
        instructions.value ? instructionsId.value : undefined,
        tip.value ? tipId.value : undefined,
        warning.value ? warningId.value : undefined,
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
    required: required.value,
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

    if (props.fieldContext?.required) {
      attributes.required = true;
      attributes['aria-required'] = 'true';
    }

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
  const visible = computed(() =>
    props.element.visibleWhen
      ? evaluateVisibilityCondition(
          props.element.visibleWhen,
          props.context.values,
          props.context.bindingScope
        )
      : true
  );
  const tabs = computed(() => props.element.children ?? []);
  const tabsId = useId();
  const selectedTabKey = ref<string>();

  watch(
    tabs,
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

  function selectTabKey(key: string | null | undefined): void {
    selectedTabKey.value = key ?? undefined;
  }

  function tabId(key: string | null | undefined): string {
    return `${tabsId}-tab-${encodeURIComponent(key ?? '')}`;
  }

  function panelId(key: string | null | undefined): string {
    return `${tabsId}-panel-${encodeURIComponent(key ?? '')}`;
  }

  function navigateTabs(event: KeyboardEvent): void {
    const tab = event.currentTarget as HTMLElement;
    const tabElements = Array.from(
      tab.parentElement?.querySelectorAll<HTMLElement>('[role="tab"]') ?? []
    );
    const index = tabElements.indexOf(tab);
    let nextIndex = -1;

    switch (event.key) {
      case 'Home':
        nextIndex = 0;
        break;
      case 'End':
        nextIndex = tabElements.length - 1;
        break;
      case 'ArrowRight':
      case 'ArrowDown':
        nextIndex = (index + 1) % tabElements.length;
        break;
      case 'ArrowLeft':
      case 'ArrowUp':
        nextIndex = (index - 1 + tabElements.length) % tabElements.length;
        break;
    }

    if (nextIndex === -1) {
      return;
    }

    event.preventDefault();
    tabElements[nextIndex]?.click();
    tabElements[nextIndex]?.focus();
  }
</script>

<template>
  <div
    v-if="element.type === 'craft:field'"
    v-show="visible"
    data-form-element="craft:field"
    :style="{width}"
  >
    <label v-if="label" :id="labelId" :for="fieldInputId">
      {{ label }}<span v-if="required" aria-hidden="true"> *</span>
    </label>
    <p v-if="instructions" :id="instructionsId" data-form-element-instructions>
      {{ instructions }}
    </p>
    <p v-if="tip" :id="tipId" data-form-element-tip>{{ tip }}</p>
    <p v-if="warning" :id="warningId" data-form-element-warning role="alert">
      {{ warning }}
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

  <div
    v-else-if="element.type === 'craft:group' || element.type === 'craft:tab'"
    v-show="visible"
    :data-form-element="element.type"
    :style="{width}"
  >
    <FormElementRenderer
      v-for="(child, index) in element.children"
      :key="reconciliationKey(child, index)"
      :element="child"
      :context="context"
    />
  </div>

  <div
    v-else-if="element.type === 'craft:tabs'"
    v-show="visible"
    data-form-element="craft:tabs"
    :style="{width}"
  >
    <div v-if="tabs.length > 1" role="tablist" data-form-tab-navigation>
      <craft-tab
        v-for="tab in tabs"
        :key="`tab:${tab.key}`"
        role="tab"
        :id="tabId(tab.key)"
        :aria-controls="panelId(tab.key)"
        :aria-selected="tab.key === selectedTabKey"
        :tabindex="tab.key === selectedTabKey ? 0 : -1"
        :selected="tab.key === selectedTabKey || undefined"
        @click="selectTabKey(tab.key)"
        @keydown="navigateTabs"
      >
        {{ tab.props?.label }}
        <craft-indicator
          v-if="tab.props?.hasErrors === true"
          fill="danger"
          :label="t('Contains errors')"
          data-form-tab-errors
        />
      </craft-tab>
    </div>
    <div
      v-for="tab in tabs"
      :key="`panel:${tab.key}`"
      v-show="tabs.length === 1 || tab.key === selectedTabKey"
      :role="tabs.length > 1 ? 'tabpanel' : undefined"
      :id="tabs.length > 1 ? panelId(tab.key) : undefined"
      :aria-labelledby="tabs.length > 1 ? tabId(tab.key) : undefined"
      :data-form-tab-panel="tab.key"
    >
      <FormElementRenderer
        v-for="(child, index) in tab.children"
        :key="reconciliationKey(child, index)"
        :element="child"
        :context="context"
      />
    </div>
  </div>

  <div v-else v-show="visible" :style="{width}">
    <div v-if="missingRenderer" data-form-element-missing-renderer>
      {{ missingRendererMessage }}
    </div>
    <div v-else-if="rendererFailure" data-form-element-failed-renderer>
      {{ failedRendererMessage }}
    </div>
    <component
      :is="renderer"
      v-else-if="renderer"
      :config="(element.props ?? {}) as Record<string, JsonValue>"
      :attributes="attributes"
      :binding="binding"
      @update:value="updateValue"
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
