<script setup lang="ts">
  import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
  import type {FormControlPayload, FormValues} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';

  type ConditionBuilderProps = {
    conditionClass: string;
    queryParams: string[];
    forProjectConfig: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<ConditionBuilderProps>;
    value: FormValues;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: FormValues, kind: 'discrete'): void;
  }>();
  let headHtml = '';
  let bodyHtml = '';
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.control.props, () => props.editable],
    events: ['input', 'change', 'drop', 'htmx:afterSwap'],
    async render() {
      const response = await actionClient.post<{
        html: string;
        headHtml: string;
        bodyHtml: string;
      }>('fields/render-condition-builder', {
        value: props.value,
        ...props.control.props,
        name: inputName(props.control.path),
        disabled: !props.editable,
      });

      headHtml = response.data.headHtml;
      bodyHtml = response.data.bodyHtml;

      return response.data.html;
    },
    async afterRender() {
      await appendHeadHtml(headHtml);
      await appendBodyHtml(bodyHtml);
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
        value: FormValues;
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
  <div ref="host" v-html="html"></div>
</template>
