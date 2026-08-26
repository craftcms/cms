<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {actionClient} from '@craftcms/ui';
  import type {FormControlPayload, FormValues} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';

  const props = defineProps<{
    control: FormControlPayload;
    value: FormValues[];
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: FormValues[], kind: 'discrete'): void;
  }>();
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.editable],
    async render() {
      const response = await actionClient.post<{html: string}>(
        'fields/render-grouped-entry-type-manager',
        {
          value: props.value,
          name: inputName(props.control.path),
          disabled: !props.editable,
        }
      );

      return response.data.html;
    },
    readValue(host) {
      const name = `${inputName(props.control.path)}[]`;
      const values = [...host.querySelectorAll<HTMLInputElement>('input[name]')]
        .filter((input) => input.name === name)
        .map((input) => input.value)
        .filter(Boolean)
        .map((value) => {
          // SAFETY: These hidden values are JSON objects rendered by the same
          // grouped-entry-type control.
          return JSON.parse(value) as FormValues;
        });

      return values;
    },
    update: (value) => emit('update:value', value, 'discrete'),
  });
</script>

<template>
  <div ref="host">
    <DynamicHtmlRenderer :html="html" />
  </div>
</template>
