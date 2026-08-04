<script setup lang="ts">
  import {reactive} from 'vue';
  import FormNode from './FormNode.vue';
  import type {FormPayload} from './types';

  const props = defineProps<{payload: FormPayload}>();
  const values = reactive(structuredClone(props.payload.values));
</script>

<template>
  <ul v-if="payload.globalErrors.length" class="error-list" role="alert">
    <li v-for="error in payload.globalErrors" :key="error">{{ error }}</li>
  </ul>
  <FormNode
    v-for="node in payload.nodes"
    :key="node.uid ?? node.control?.path.join('.')"
    :node="node"
    :values="values"
    :errors="payload.errors"
  />
</template>
