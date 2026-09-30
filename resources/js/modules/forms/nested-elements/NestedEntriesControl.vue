<script setup lang="ts">
  import {provide} from 'vue';
  import {inputName} from '../runtime';
  import type {FormControlPayload, FormValue} from '../types';
  import NestedEntriesCardsShell from './NestedEntriesCardsShell.vue';
  import {
    NestedEntriesControlKey,
    type NestedEntriesControlContext,
  } from './nested-entries-context';
  import type {NestedEntriesProps} from './nested-entries';

  const props = defineProps<{
    control: FormControlPayload<NestedEntriesProps>;
    value: FormValue;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: FormValue, kind: 'discrete'): void;
  }>();

  provide<NestedEntriesControlContext>(NestedEntriesControlKey, {
    path: props.control.path,
    markModified: () => emit('update:value', '*', 'discrete'),
  });
</script>

<template>
  <div class="nested-entries">
    <input
      v-if="editable && value === '*'"
      type="hidden"
      :name="inputName(control.path)"
      value="*"
    />
    <p v-if="control.props.unavailableMessage">
      {{ control.props.unavailableMessage }}
    </p>
    <NestedEntriesCardsShell
      v-else
      :control="control.props"
      :editable="editable"
    />
  </div>
</template>

<style scoped>
  .nested-entries {
    min-width: 0;
    max-width: 100%;
  }
</style>
