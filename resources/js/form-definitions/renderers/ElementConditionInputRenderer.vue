<script setup lang="ts">
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    useAttrs,
    useTemplateRef,
  } from 'vue';
  import {appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
  import ConditionsController from '@actions/ConditionsController';
  import {expandFormData} from '@/common/utils/forms';
  import {valueAt} from '../binding';
  import type {FormElementBinding, FormValues, JsonValue} from '../types';
  import '@craftcms/ui/components/element-condition/element-condition';
  import '@craftcms/ui/components/spinner/spinner';

  type ConditionConfig = Record<string, JsonValue> & {
    class: string;
    conditionRules?: Array<Record<string, JsonValue>>;
  };

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: ConditionConfig | null];
  }>();

  const attrs = useAttrs();
  const hostProperties = computed(() => ({...props.attributes, ...attrs}));
  const container =
    useTemplateRef<HTMLElementTagNameMap['craft-element-condition']>(
      'container'
    );
  const builderHtml = ref('');
  const error = ref('');
  let observer: MutationObserver | undefined;

  onMounted(renderBuilder);
  onBeforeUnmount(() => observer?.disconnect());

  async function renderBuilder(): Promise<void> {
    try {
      const response = await fetch(ConditionsController.show().url, {
        method: 'POST',
        headers: {
          Accept: 'text/html',
          'HX-Request': 'true',
          ...(window as any).Craft?._actionHeaders?.(),
        },
        body: builderRequest(),
      });

      if (!response.ok) {
        throw new Error(
          `Condition builder request failed (${response.status}).`
        );
      }

      const template = document.createElement('template');
      template.innerHTML = await response.text();

      for (const head of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-head-html'
      )) {
        await appendHeadHtml(head.innerHTML);
        head.remove();
      }

      for (const body of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-body-html'
      )) {
        await appendBodyHtml(body.innerHTML);
        body.remove();
      }

      builderHtml.value = template.innerHTML;
      await nextTick();
      initializeBuilder();
    } catch (exception) {
      const reason =
        exception instanceof Error
          ? exception.message
          : t('Unknown condition builder error.');

      error.value = t(
        'Element Condition option “conditionRules” could not be rendered for Form Definition output: {reason}',
        {reason}
      );
    }
  }

  function builderRequest(): FormData {
    const request = new FormData();
    const name = String(props.attributes.name);
    const builderConfig = objectConfig(props.config.builderConfig);
    const conditionClass = String(props.config.conditionClass);
    const condition = objectConfig(props.binding?.value);
    const conditionRules = Array.isArray(condition.conditionRules)
      ? condition.conditionRules
      : [];

    request.append(
      'config',
      JSON.stringify({
        ...builderConfig,
        class: conditionClass,
        id: String(props.attributes.id),
        name,
        mainTag: 'div',
        sortable: props.config.sortable !== false,
        forProjectConfig: true,
        addRuleLabel: props.config.addRuleLabel,
      })
    );
    request.append(`${name}[class]`, String(condition.class ?? conditionClass));
    request.append(`${name}[config]`, JSON.stringify(builderConfig));
    appendFormValue(request, `${name}[conditionRules]`, conditionRules);

    return request;
  }

  function initializeBuilder(): void {
    if (!container.value) {
      return;
    }

    container.value.initialize();

    observer = new MutationObserver(() => {
      syncValue();
    });
    observer.observe(container.value, {childList: true, subtree: true});
  }

  function syncValue(): void {
    if (!container.value || props.binding?.readOnly) {
      return;
    }

    queueMicrotask(() => {
      if (!container.value) {
        return;
      }

      const hostForm = container.value.closest('form');

      if (!hostForm) {
        throw new Error(
          'Element Condition Form Elements must be rendered within a form.'
        );
      }

      const values = expandFormData(new FormData(hostForm));
      const condition = valueAt(
        values as FormValues,
        htmlNameToPath(String(props.attributes.name))
      );

      if (!isConditionConfig(condition)) {
        emit('update:value', null);

        return;
      }

      if (Array.isArray(condition.conditionRules)) {
        condition.conditionRules = condition.conditionRules.filter(
          (rule): rule is Record<string, JsonValue> =>
            rule !== null && typeof rule === 'object' && !Array.isArray(rule)
        );
      }

      emit('update:value', condition.conditionRules?.length ? condition : null);
    });
  }

  function appendFormValue(
    formData: FormData,
    name: string,
    value: unknown
  ): void {
    if (Array.isArray(value)) {
      value.forEach((item, index) =>
        appendFormValue(formData, `${name}[${index}]`, item)
      );

      return;
    }

    if (value !== null && typeof value === 'object') {
      Object.entries(value).forEach(([key, item]) =>
        appendFormValue(formData, `${name}[${key}]`, item)
      );

      return;
    }

    formData.append(name, value == null ? '' : String(value));
  }

  function objectConfig(value: unknown): Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value)
      ? (value as Record<string, unknown>)
      : {};
  }

  function isConditionConfig(value: unknown): value is ConditionConfig {
    return typeof objectConfig(value).class === 'string';
  }

  function htmlNameToPath(name: string): string {
    return name.replaceAll('[', '.').replaceAll(']', '');
  }
</script>

<template>
  <craft-element-condition
    ref="container"
    v-bind="hostProperties"
    class="condition-container"
    :readonly="binding?.readOnly ?? false"
    @input="syncValue"
    @change="syncValue"
  >
    <craft-spinner v-if="!builderHtml && !error"></craft-spinner>
    <p v-else-if="error" class="error" role="alert">{{ error }}</p>
    <div v-else v-html="builderHtml"></div>
  </craft-element-condition>
</template>
