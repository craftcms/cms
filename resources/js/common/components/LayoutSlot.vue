<script setup lang="ts">
  /**
   * Renders its children into the ambient `AppLayout`'s matching
   * `LayoutSlotOutlet`. Content stays compiled in the page's scope, so
   * all page bindings (props, form state, refs, …) remain reactive even
   * though the DOM is teleported into the layout.
   */
  import {onBeforeUnmount, onMounted} from 'vue';
  import {
    registerLayoutSlot,
    unregisterLayoutSlot,
  } from '@/common/composables/layoutSlots';

  const props = defineProps<{name: string}>();

  // Register after mount, not during setup: registration mutates shared
  // reactive state, and doing that mid-render forces the parent layout to
  // re-render while this subtree is still mounting, which races the
  // deferred Teleport. Outlet targets are always in the DOM (hidden while
  // unfilled), so the teleport doesn't depend on registration timing.
  onMounted(() => registerLayoutSlot(props.name));
  onBeforeUnmount(() => unregisterLayoutSlot(props.name));
</script>

<template>
  <Teleport defer :to="`[data-layout-slot='${name}']`">
    <slot></slot>
  </Teleport>
</template>
