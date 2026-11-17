<script setup lang="ts">
  import {computed} from 'vue';
  import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';
  import ServerRenderedControlFrame from './ServerRenderedControlFrame.vue';

  type FieldSelectProps = {
    limit?: number;
    create?: boolean;
  };

  const props = defineProps<{
    control: UiControlPayload<FieldSelectProps>;
    value: unknown;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: number | null, kind: 'discrete'): void;
  }>();
  let headHtml = '';
  let bodyHtml = '';
  const {host, html, status, retry} = useServerRenderedControl({
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

  /** Choose and Create are hidden once a selection reaches the `limit`. */
  const placeholderButtons = computed(() => {
    if (props.value != null && props.control.props.limit) {
      return 0;
    }

    return props.control.props.create && props.editable ? 2 : 1;
  });
</script>

<template>
  <ServerRenderedControlFrame
    :status="status"
    :has-html="Boolean(html)"
    :chips="value == null ? 0 : 1"
    :buttons="placeholderButtons"
    @retry="retry"
  >
    <div ref="host" v-html="html"></div>
  </ServerRenderedControlFrame>
</template>
