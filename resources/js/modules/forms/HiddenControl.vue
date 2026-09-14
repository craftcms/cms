<script setup lang="ts">
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';

  defineProps<{
    control: FormControlPayload;
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
