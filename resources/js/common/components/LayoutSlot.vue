<script setup lang="ts">
  /**
   * Renders its children into the enclosing screen shell's matching
   * `LayoutSlotOutlet`. Content stays compiled in the page's scope, so
   * all page bindings (props, form state, refs, …) remain reactive even
   * though the DOM is teleported into the shell.
   */
  import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {isLayoutSlotName} from '@/common/composables/layoutSlotNames';
  import {useLayoutSlotRegistry} from '@/common/composables/layoutSlots';

  const props = defineProps<{
    name: string;
    /**
     * Target a specific shell's outlets. Only needed to reach past the
     * nearest shell — the registry resolves the right one by default.
     */
    scope?: string;
  }>();

  if (import.meta.env.DEV && !isLayoutSlotName(props.name)) {
    console.warn(
      `[LayoutSlot] "${props.name}" isn't a known layout slot, so nothing will render it.`
    );
  }

  const registry = useLayoutSlotRegistry();
  const scope = computed(() => props.scope ?? registry.scope);

  // The outlet selector is scoped to one shell: a slideout keeps the base
  // page mounted, so an unscoped `[data-layout-slot=…]` would match the base
  // page's outlet first and teleport the slideout's content into the page
  // behind it.
  const target = computed(
    () =>
      `[data-layout-scope='${scope.value}'][data-layout-slot='${props.name}']`
  );

  // Register after mount, not during setup: registration mutates shared
  // reactive state, and doing that mid-render forces the parent shell to
  // re-render while this subtree is still mounting, which races the
  // deferred Teleport. Outlets stay in the DOM while unfilled, so the teleport
  // doesn't depend on registration timing.
  // A replaced target strands whatever was teleported into it, so remount the
  // teleport against the new one.
  const teleportKey = ref(0);
  watch(
    () => registry.targetRevision(props.name),
    (_revision, previous) => {
      if (previous > 0) {
        teleportKey.value++;
      }
    }
  );

  // Some outlets only render once the page has configured the shell — the
  // Save button's needs a form — so wait for the outlet rather than mount
  // against a target that isn't there. An explicit `scope` reaches another
  // shell's registry, which this one can't see into.
  const hasTarget = computed(
    () => props.scope !== undefined || registry.targetRevision(props.name) > 0
  );

  onMounted(() => registry.register(props.name));
  onBeforeUnmount(() => registry.unregister(props.name));
</script>

<template>
  <Teleport v-if="hasTarget" :key="teleportKey" defer :to="target">
    <slot></slot>
  </Teleport>
</template>
