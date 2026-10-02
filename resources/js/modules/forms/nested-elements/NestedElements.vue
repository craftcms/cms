<script setup lang="ts">
  import {provide} from 'vue';
  import {
    NestedOwnerEditorKey,
    type NestedOwnerEditor,
  } from '@/modules/elements/nested-owner';
  import NestedElementsCardsShell from './NestedElementsCardsShell.vue';
  import {
    NestedElementsControlKey,
    type NestedElementsControlContext,
  } from './nested-elements-context';
  import NestedElementsIndex from './NestedElementsIndex.vue';
  import type {NestedElementsProps} from './nested-elements';

  const props = defineProps<{
    path: string[];
    nested: NestedElementsProps;
    editable: boolean;
    /**
     * Stands in for the owner's element editor when the manager lives outside
     * one, e.g. on an owner screen of its own (see `savedNestedOwner()`).
     */
    owner?: NestedOwnerEditor;
  }>();
  const emit = defineEmits<{
    modified: [];
  }>();

  provide<NestedElementsControlContext>(NestedElementsControlKey, {
    path: props.path,
    markModified: () => emit('modified'),
  });

  if (props.owner) {
    provide(NestedOwnerEditorKey, props.owner);
  }
</script>

<template>
  <div class="nested-elements">
    <slot />
    <p v-if="nested.unavailableMessage">
      {{ nested.unavailableMessage }}
    </p>
    <NestedElementsIndex
      v-else-if="nested.viewMode === 'index'"
      :control="nested"
      :editable="editable"
    />
    <NestedElementsCardsShell v-else :control="nested" :editable="editable" />
  </div>
</template>

<style scoped>
  .nested-elements {
    min-width: 0;
    max-width: 100%;
  }
</style>
