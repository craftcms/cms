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

  const props = defineProps<{
    items: Array<ElementActionMenuItem>;
    /**
     * The entry type as currently selected, which the sidebar can change
     * without saving, so the settings slideout follows the field rather than
     * the stored value.
     */
    currentEntryTypeId?: string | number | null;
  }>();

  const actions = useElementActionMenu(() => props.items, {
    currentEntryTypeId: () => props.currentEntryTypeId ?? null,
  });
</script>

<template>
  <ActionMenu v-if="actions.length" :actions="actions" />
</template>
