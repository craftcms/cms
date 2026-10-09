<script setup lang="ts">
  /**
   * The element's own actions: Validate, Copy, Delete, and the like. Their
   * behaviors are dispatched client-side rather than through registered jQuery
   * handlers. Renders nothing when there are none.
   */
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {
    useElementActionMenu,
    type ElementActionMenuItem,
  } from '@/modules/elements/composables/useElementActionMenu';
  import type {SlideoutController} from '@/common/slideouts';

  const props = withDefaults(
    defineProps<{
      items: Array<ElementActionMenuItem>;
      /**
       * The entry type as currently selected, which the sidebar can change
       * without saving, so the settings slideout follows the field rather than
       * the stored value.
       */
      currentEntryTypeId?: string | number | null;
      /** The slideout the element is being edited in, which its actions act on. */
      slideout?: SlideoutController | null;
      /**
       * Passed to the invoker; see `craft-button`'s `flush`. Only right where
       * the button is the last thing in a row; beside other buttons it would
       * overlap them.
       */
      flush?: boolean | string;
    }>(),
    {
      flush: true,
    }
  );

  const actions = useElementActionMenu(() => props.items, {
    currentEntryTypeId: () => props.currentEntryTypeId ?? null,
    slideout: props.slideout ?? null,
  });
</script>

<template>
  <ActionMenu v-if="actions.length" :actions="actions" :flush="flush" />
</template>
