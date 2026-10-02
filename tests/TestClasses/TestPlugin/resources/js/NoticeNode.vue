<script setup lang="ts">
  import type {FormNodePayload} from '@/modules/forms/types';

  defineProps<{
    node: FormNodePayload<{message: string}>;
  }>();
</script>

<template>
  <p :data-form-node="node.uid" data-test-plugin-notice>
    {{ node.props.message }}
  </p>
</template>
