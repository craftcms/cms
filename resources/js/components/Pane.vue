<script setup lang="ts">
  import {type Component, computed, useSlots} from 'vue';

  const slots = useSlots();

  withDefaults(
    defineProps<{
      as?: Component | string;
      variant?: 'plain' | 'error';
      hideHeader?: boolean;
      hideFooter?: boolean;
    }>(),
    {
      as: 'div',
      hideHeader: false,
      hideFooter: false,
    }
  );

  const showHeader = computed(() => {
    return slots.header;
  });

  const showFooter = computed(() => {
    return (
      slots.footer ||
      slots.actions ||
      slots['primary-action'] ||
      slots['secondary-action']
    );
  });
</script>

<template>
  <component :is="as" class="pane" v-bind="$attrs">
    <slot name="header" v-if="showHeader">
      <div class="pane__header">
        <slot name="title"></slot>
        <slot name="header-actions"></slot>
      </div>
    </slot>

    <slot name="body">
      <div class="pane__body">
        <slot></slot>
      </div>
    </slot>
    <slot name="footer" v-if="showFooter">
      <div class="pane__footer">
        <slot name="actions">
          <div class="actions">
            <slot name="primary-action"></slot>
            <slot name="secondary-action"></slot>
          </div>
        </slot>
      </div>
    </slot>
  </component>
</template>

<style scoped lang="scss">
  .pane {
    --_pane-spacing: var(--c-spacing-lg);

    background-color: var(--c-pane-bg);
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-pane-radius);
    border: var(--c-pane-border);
    display: grid;
  }

  .pane__header {
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing);
  }

  .pane__body {
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing);
  }

  .pane__footer {
    border-top: 1px solid var(--c-color-neutral-border-subtle);
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing);
  }

  .actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
</style>
