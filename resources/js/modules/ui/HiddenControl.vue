<script setup lang="ts">
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  defineProps<{
    control: UiControlPayload;
    value: unknown;
    editable: boolean;
  }>();
</script>

<template>
  <input
    v-if="editable"
    type="hidden"
    :name="inputName(control.path)"
    :value="String(value ?? '')"
  />
</template>
