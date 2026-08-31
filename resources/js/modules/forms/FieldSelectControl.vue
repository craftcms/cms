<script setup lang="ts">
  import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';

  type FieldSelectProps = {
    limit?: number;
    create?: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<FieldSelectProps>;
    value: unknown;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: number | null, kind: 'discrete'): void;
  }>();
  let headHtml = '';
  let bodyHtml = '';
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.control.props, () => props.editable],
    events: ['change'],
    async render() {
      const response = await actionClient.post<{
        html: string;
        headHtml: string;
        bodyHtml: string;
      }>('fields/render-field-select', {
        value: props.value ?? null,
        ...props.control.props,
        create: Boolean(props.control.props.create),
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
    readValue(host) {
      if (!props.editable) {
        return;
      }

      const chip = host.querySelector<HTMLElement>(
        'ul > li:not([data-removing]) > craft-chip'
      );
      const id = chip?.dataset.id;

      return id ? Number(id) : null;
    },
    update: (value) =>
      emit('update:value', value == null ? null : Number(value), 'discrete'),
  });
</script>

<template>
  <div ref="host" v-html="html"></div>
</template>
