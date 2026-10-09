<script setup lang="ts">
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {UiFailure, uiChangeFromEvent} from './runtime';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  defineOptions({name: 'UiNode'});

  const props = defineProps<{
    node: UiNodePayload;
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
  const invalidate = inject(UiFailure)!;
  const components = getCurrentInstance()!.appContext.components;
  const component = computed(() => {
    const component = components[props.node.component];

    if (!component) {
      throw new Error(
        `Failed to render UI Node [${props.node.type}] with component [${props.node.component}] at [${identity()}]: component is not registered.`
      );
    }

    return component;
  });

  onErrorCaptured((error) => {
    invalidate(
      `Failed to render UI Node [${props.node.type}] with component [${props.node.component}] at [${identity()}]: ${error instanceof Error ? error.message : String(error)}`
    );

    return false;
  });

  /**
   * Only tab nodes take these. Passed to any other node, they fall through as
   * attributes, which a node that renders a fragment can't place.
   */
  const tabProps = computed(() =>
    props.node.component === 'craft:tab'
      ? {initiallyHidden: props.initiallyHidden, tabButtonId: props.tabButtonId}
      : {}
  );

  function identity(): string {
    return props.node.uid ?? props.node.control?.path.join('.') ?? 'unknown';
  }

  function onChange(change: UiChange | Event): void {
    const uiChange = uiChangeFromEvent(change);

    if (uiChange) {
      emit('change', uiChange);
    }
  }
</script>

<template>
  <component
    :is="component"
    :node="node"
    :values="values"
    :errors="errors"
    :touched-paths="touchedPaths"
    :scope="scope"
    :refreshable="refreshable"
    v-bind="tabProps"
    @change="onChange"
  />
</template>
