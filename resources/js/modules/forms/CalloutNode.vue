<script setup lang="ts">
  import type {FormNodePayload} from './types';

  defineProps<{
    node: FormNodePayload<{
      html: string;
      variant: string;
      appearance?: string;
      icon?: string;
      dismissible: boolean;
      width: number;
    }>;
  }>();
</script>

<template>
  <craft-callout
    :class="`width-${node.props.width}`"
    :data-form-node="node.uid"
    :variant="node.props.variant"
    v-bind="node.props.appearance ? {appearance: node.props.appearance} : {}"
    :icon="node.props.icon"
    :data-dismissible="node.props.dismissible || undefined"
    v-html="node.props.html"
  ></craft-callout>
</template>
