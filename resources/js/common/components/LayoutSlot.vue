<script setup lang="ts">
    /**
     * Renders its children into the enclosing screen shell's matching
     * `LayoutSlotOutlet`. Content stays compiled in the page's scope, so
     * all page bindings (props, form state, refs, …) remain reactive even
     * though the DOM is teleported into the shell.
     */
    import {computed, onBeforeUnmount, onMounted} from 'vue';
    import {useLayoutSlotRegistry} from '@/common/composables/layoutSlots';

    const props = defineProps<{
        name: string;
        /**
         * Target a specific shell's outlets. Only needed to reach past the
         * nearest shell — the registry resolves the right one by default.
         */
        scope?: string;
    }>();

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
    // deferred Teleport. Outlet targets are always in the DOM (hidden while
    // unfilled), so the teleport doesn't depend on registration timing.
    onMounted(() => registry.register(props.name));
    onBeforeUnmount(() => registry.unregister(props.name));
</script>

<template>
    <Teleport defer :to="target">
        <slot></slot>
    </Teleport>
</template>
