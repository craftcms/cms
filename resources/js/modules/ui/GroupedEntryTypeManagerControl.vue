<script setup lang="ts">
  import {computed} from 'vue';
  import {actionClient} from '@craftcms/ui';
  import type {UiControlPayload, UiValues} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';
  import ServerRenderedControlFrame from './ServerRenderedControlFrame.vue';

  const props = defineProps<{
    control: UiControlPayload;
    value: UiValues[];
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: UiValues[], kind: 'discrete'): void;
  }>();
  const {host, html, status, retry} = useServerRenderedControl({
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
          return JSON.parse(value) as UiValues;
        });

      return values;
    },
    update: (value) => emit('update:value', value, 'discrete'),
  });

  /** Each group's entry type count. An empty manager still shows one group. */
  const placeholderGroups = computed(() => {
    const counts = new Map<UiValues['group'], number>();
    for (const entryType of props.value ?? []) {
      counts.set(entryType.group, (counts.get(entryType.group) ?? 0) + 1);
    }

    return counts.size ? [...counts.values()] : [0];
  });
</script>

<template>
  <ServerRenderedControlFrame
    :status="status"
    :has-html="Boolean(html)"
    :groups="placeholderGroups"
    :buttons="editable ? 2 : 1"
    :add-group="editable"
    @retry="retry"
  >
    <!-- v-html, not DynamicHtmlRenderer: Vue would compile the select
         <template> into child nodes, leaving its content empty. -->
    <div ref="host" v-html="html"></div>
  </ServerRenderedControlFrame>
</template>
