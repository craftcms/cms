<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {actionClient} from '@craftcms/ui';
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';

  type ConditionBuilderProps = {
    conditionClass: string;
    queryParams: string[];
    forProjectConfig: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<ConditionBuilderProps>;
    value: Record<string, unknown>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: Record<string, unknown>,
      kind: 'discrete'
    ): void;
  }>();
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.control.props, () => props.editable],
    async render() {
      const response = await actionClient.post<{html: string}>(
        'fields/render-condition-builder',
        {
          value: props.value,
          ...props.control.props,
          name: inputName(props.control.path),
          disabled: !props.editable,
        }
      );

      return response.data.html;
    },
    async readValue(host) {
      if (!props.editable) {
        return;
      }

      const form = host.closest('form');
      if (!form) {
        return;
      }

      const serializedForm = new URLSearchParams(
        [...new FormData(form).entries()].map(([name, value]) => [
          name,
          String(value),
        ])
      ).toString();
      const response = await actionClient.post<{
        value: Record<string, unknown>;
      }>('fields/normalize-condition-builder', {
        serialized: serializedForm,
        path: props.control.path,
      });
      return response.data.value;
    },
    update: (value) => emit('update:value', value, 'discrete'),
  });
</script>

<template>
  <div ref="host">
    <DynamicHtmlRenderer :html="html" />
  </div>
</template>
