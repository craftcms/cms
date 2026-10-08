<script setup lang="ts">
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {FormFailure, formChangeFromEvent} from './runtime';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  defineOptions({name: 'FormNode'});

  const props = defineProps<{
    node: FormNodePayload;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
    initiallyHidden?: boolean;
    tabButtonId?: string;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
  const invalidate = inject(FormFailure)!;
  const components = getCurrentInstance()!.appContext.components;
  const component = computed(() => {
    const component = components[props.node.component];

    if (!component) {
      throw new Error(
        `Failed to render Form Node [${props.node.type}] with component [${props.node.component}] at [${identity()}]: component is not registered.`
      );
    }

    return component;
  });

  onErrorCaptured((error) => {
    invalidate(
      `Failed to render Form Node [${props.node.type}] with component [${props.node.component}] at [${identity()}]: ${error instanceof Error ? error.message : String(error)}`
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

  function onChange(change: FormChange | Event): void {
    const formChange = formChangeFromEvent(change);

    if (formChange) {
      emit('change', formChange);
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
