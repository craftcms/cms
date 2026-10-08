<script setup lang="ts">
  import {computed} from 'vue';
  import {inputName, valueAt} from './runtime';
  import type {UiNodePayload, UiPayload} from './types';

  const props = defineProps<{
    node: UiNodePayload;
    values: UiPayload['values'];
  }>();
  const control = computed(() => props.node.control!);
  const value = computed(() => valueAt(props.values, control.value.path));
</script>

<template>
  <input
    v-if="control.mode === 'editable'"
    type="hidden"
    :name="inputName(control.path)"
    :value="String(value ?? '')"
  />
</template>
