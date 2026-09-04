<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ActionList from '@/common/components/ActionList.vue';
  import type {ActionItem} from '@/common/types';

  export type ActionItems = Array<ActionItem>;

  const props = withDefaults(
    defineProps<{
      icon?: string;
      label?: string | null;
      actions: ActionItems;
      buttonVariant?: ButtonVariant;
    }>(),
    {
      icon: 'ellipsis',
      label: t('Actions'),
      buttonVariant: ButtonVariant.Plain,
    }
  );

  /**
   * Destructive items sink to the bottom, stably.
   *
   * `craft-action-menu` does this itself, but only for the menus it builds
   * from its `actions` property — and this one is slotted, so the convention
   * has to be applied here instead.
   */
  const sorted = computed<ActionItems>(() => [
    ...props.actions.filter((action) => !isDanger(action)),
    ...props.actions.filter((action) => isDanger(action)),
  ]);

  function isDanger(action: ActionItem): boolean {
    return 'variant' in action && action.variant === 'danger';
  }

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

   The items are a different matter: they change, and they're rendered here
   rather than by the element so link actions can be `CpLink`s and make Inertia
   visits. `craft-popover` moves unslotted children into a `slot="content"`
   container it creates once, which would strand anything Vue added afterwards
   outside the slot — so the wrapper below is ours, which stops the auto-wrap
   from running at all. Keep every comment outside `craft-action-menu`: the
   auto-wrap counts any non-empty child node, comment nodes included.
   */
</script>

<template>
  <craft-action-menu :icon="icon" :label="label ?? undefined">
    <span slot="invoker" style="display: inline-flex" v-once>
      <slot name="invoker" :label="label" :attributes="{slot: 'invoker'}">
        <craft-button
          type="button"
          size="small"
          :icon="icon"
          :aria-label="label"
          :variant="buttonVariant"
        >
        </craft-button>
      </slot>
    </span>
    <div slot="content">
      <ActionList :actions="sorted" as="craft-action-item" />
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-menu :deep(craft-action-item) {
    min-width: 200px;
  }
</style>
