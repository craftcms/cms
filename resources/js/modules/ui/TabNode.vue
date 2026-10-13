<script setup lang="ts">
  import '@craftcms/ui/components/field-group/field-group';
  import UiNodeList from './UiNodeList.vue';
  import {uiTabPanelId} from './runtime';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  const props = defineProps<{
    node: UiNodePayload<{label: string}>;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
    initiallyHidden?: boolean;
    tabButtonId?: string;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();

  function id(): string {
    return uiTabPanelId(props.node.uid!, props.scope);
  }
</script>

<template>
  <section
    :id="id()"
    :class="{hidden: initiallyHidden}"
    :role="tabButtonId ? 'tabpanel' : undefined"
    :aria-label="node.props.label"
    :aria-labelledby="tabButtonId"
    :data-id="id()"
    :data-ui-tab="node.uid"
    :data-layout-tab="node.uid"
  >
    <craft-field-group>
      <UiNodeList
        :nodes="node.children ?? []"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="refreshable"
        @change="emit('change', $event)"
      />
    </craft-field-group>
  </section>
</template>
