<script setup lang="ts">
  import {type ActionMenuItem, ButtonVariant, t} from '@craftcms/ui';
  import {
    type Component,
    computed,
    createVNode,
    getCurrentInstance,
    onBeforeUnmount,
    render as vueRender,
  } from 'vue';
  import type {ActionItem} from '@/common/types';

  interface ActionItemDisplay {
    type: 'display';
    is: Component;
  }

  export type ActionItems = Array<ActionItem | ActionItemDisplay>;

  const props = withDefaults(
    defineProps<{
      icon?: string;
      label?: string | null;
      actions: ActionItems;
    }>(),
    {
      icon: 'ellipsis',
      label: t('Actions'),
    }
  );

  // Containers that host Vue-rendered `display` components. We keep references
  // so we can tear down the Vue render trees on unmount / re-compute.
  let displayContainers: HTMLElement[] = [];
  const appContext = getCurrentInstance()?.appContext;

  function clearDisplayContainers() {
    for (const container of displayContainers) {
      // Unmount the Vue subtree to avoid leaks.
      vueRender(null, container);
    }
    displayContainers = [];
  }

  /**
   * Convert a Vue `display` component into a DOM node the web component can
   * consume via its `{type: 'display', node}` contract. The normalize / sort /
   * item-rendering logic itself lives ONLY in the web component — this adapter
   * just bridges Vue components into DOM nodes.
   */
  function displayToNode(component: Component): Node {
    const container = document.createElement('div');
    container.style.display = 'contents';
    const vnode = createVNode(component);
    if (appContext) {
      vnode.appContext = appContext;
    }
    vueRender(vnode, container);
    displayContainers.push(container);
    return container;
  }

  // Map the Vue-flavored action list onto the web component's `ActionMenuItem`
  // descriptors. `display` items are the only ones needing conversion; every
  // other shape passes straight through.
  const wcActions = computed<ActionMenuItem[]>(() => {
    clearDisplayContainers();

    return props.actions.map((action): ActionMenuItem => {
      if (action.type === 'display') {
        return {
          type: 'display',
          node: displayToNode((action as ActionItemDisplay).is),
        };
      }

      return action as unknown as ActionMenuItem;
    });
  });

  onBeforeUnmount(() => {
    clearDisplayContainers();
  });

  /**
   `v-once` is load-bearing, not an optimization. `craft-action-menu` (Lion
   `OverlayMixin`) imperatively relocates/restructures its own light DOM. If Vue
   keeps the invoker in its reactive patch path, a later re-render patches the
   invoker's slot fragment against DOM the element moved and throws "Cannot read
   properties of null (reading 'insertBefore')". A passive wrapper isn't enough:
   Vue's block optimization flattens dynamic descendants and patches them with
   `craft-action-menu` as the container, bypassing the wrapper. `v-once` renders
   the invoker exactly once and removes it from the block's dynamic children, so
   Vue never re-patches the overlay-managed DOM. Invokers are static triggers
   (an icon / avatar), so freezing them is safe. `inline-flex` keeps the wrapper
   sized to the invoker so the overlay positions against a real box.
   */
</script>

<template>
  <craft-action-menu
    :actions="wcActions"
    :icon="icon"
    :label="label ?? undefined"
  >
    <span slot="invoker" style="display: inline-flex" v-once>
      <slot name="invoker" :label="label" :attributes="{slot: 'invoker'}">
        <craft-button
          type="button"
          size="small"
          :icon="icon"
          :aria-label="label"
          :variant="ButtonVariant.Plain"
        >
        </craft-button>
      </slot>
    </span>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-menu :deep(craft-action-item) {
    min-width: 200px;
  }
</style>
