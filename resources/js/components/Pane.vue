<script setup lang="ts">
  import {type Component, computed, useSlots} from 'vue';

  const slots = useSlots();
  const props = withDefaults(
    defineProps<{
      as?: Component | string;
      variant?: 'plain' | 'error';
      hideHeader?: boolean;
      hideFooter?: boolean;
      title?: string;
    }>(),
    {
      as: 'div',
      hideHeader: false,
      hideFooter: false,
    }
  );

  const showHeader = computed(() => {
    return (
      slots.header || props.title || slots.title || slots['header-actions']
    );
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
        <slot name="title">
          <h1 v-if="title" class="text-lg">
            {{ title }}
          </h1>
        </slot>
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
            <slot name="secondary-action"></slot>
            <slot name="primary-action"></slot>
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
    padding-block: var(--_pane-spacing) 0;
  }

  .pane__body {
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing) calc(var(--_pane-spacing) * 1.5);
  }

  .pane__footer {
    border-top: 1px solid var(--c-color-neutral-border-subtle);
    padding-inline: var(--_pane-spacing);
    padding-block: calc(var(--_pane-spacing) / 2);
  }

  .actions {
    display: flex;
    gap: var(--c-spacing-md);
    justify-content: end;
    align-items: center;
  }
</style>
