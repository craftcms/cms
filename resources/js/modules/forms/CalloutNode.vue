<script setup lang="ts">
  import type {FormNodePayload} from './types';

  defineProps<{
    node: FormNodePayload<{
      html: string;
      variant: string;
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
    :data-dismissible="node.props.dismissible || undefined"
    v-html="node.props.html"
  ></craft-callout>
</template>
