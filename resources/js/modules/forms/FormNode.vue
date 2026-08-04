<script setup lang="ts">
  import {computed, getCurrentInstance} from 'vue';
  import type {FormNodePayload, FormPayload} from './types';

  defineOptions({name: 'FormNode'});

  const props = defineProps<{
    node: FormNodePayload;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
  }>();
  const components = getCurrentInstance()!.appContext.components;
  const component = computed(() => {
    const component = components[props.node.component];

    if (!component) {
      throw new Error(
        `Form Node component is not registered: ${props.node.component}`
      );
    }

    return component;
  });
</script>

<template>
  <component :is="component" :node="node" :values="values" :errors="errors" />
</template>
