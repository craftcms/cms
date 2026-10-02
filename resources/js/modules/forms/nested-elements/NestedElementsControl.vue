<script setup lang="ts">
  import {inputName} from '../runtime';
  import type {FormControlPayload, FormValue} from '../types';
  import NestedElements from './NestedElements.vue';
  import type {NestedElementsProps} from './nested-elements';

  defineProps<{
    control: FormControlPayload<NestedElementsProps>;
    value: FormValue;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: FormValue, kind: 'discrete'): void;
  }>();
</script>

<template>
  <NestedElements
    :path="control.path"
    :nested="control.props"
    :editable="editable"
    @modified="emit('update:value', '*', 'discrete')"
  >
    <input
      v-if="editable && value === '*'"
      type="hidden"
      :name="inputName(control.path)"
      value="*"
    />
  </NestedElements>
</template>
