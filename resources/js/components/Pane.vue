<script setup lang="ts">
  import {type Component, computed, useSlots} from 'vue';

  const slots = useSlots();
  const props = withDefaults(
    defineProps<{
      as?: Component | string;
      variant?: 'plain' | 'error' | 'code';
      appearance?: 'plain' | 'outline' | 'raised';
      hideHeader?: boolean;
      hideFooter?: boolean;
      title?: string;
      padding?: string | number;
    }>(),
    {
      padding: 'lg',
      as: 'div',
      hideHeader: false,
      hideFooter: false,
    }
  );

  // Check if string is a valid number
  function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
  }

  const showHeader = computed(() => {
    return Boolean(
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

  const computedPadding = computed(() => {
    if (props.padding === 0) {
      return 0;
    }

    if (isNumeric(props.padding)) {
      return `calc(${props.padding}rem / 16)`;
    }

    if (['sm', 'md', 'lg', 'xl'].includes(props.padding)) {
      return `var(--c-spacing-${props.padding})`;
    }

    // If it's a string, just return it
    return props.padding;
  });
</script>

<template>
  <component
    :is="as"
    :class="{
      pane: true,
      'pane--code': variant === 'code',
      'pane--error': variant === 'error',
      'pane--outline': appearance === 'outline',
      'pane--raised': appearance === 'raised',
    }"
    v-bind="$attrs"
  >
    <slot name="header" v-if="showHeader">
      <div class="pane__header">
        <slot name="title">
          <h1 v-if="title" class="text-lg">
            {{ title }}
          </h1>
        </slot>
        <div class="pane__actions">
          <slot name="header-actions"></slot>
        </div>
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
    --_pane-spacing: v-bind(computedPadding);
    --_bg-color: var(--c-pane-bg);
    --_radius: var(--c-pane-radius);

    background-color: var(--_bg-color);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--_radius);
    border: var(--c-pane-border);
    box-shadow: var(--c-pane-shadow);
    overflow: hidden;
  }

  .pane--raised {
    --c-pane-bg: var(--c-surface-raised);
    --c-pane-border: 1px solid var(--c-color-neutral-border-quiet);
    --c-pane-shadow: var(--c-shadow-raised);
  }

  .pane--code {
    background: var(--c-color-neutral-bg-quiet);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    margin-block-end: var(--c-spacing-md);
    max-height: 500px;
    overflow: auto;
  }

  .pane--outline {
    --c-pane-border: 1px solid var(--c-color-neutral-border-quiet);
  }

  .pane__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing) 0;
  }

  .pane__body {
    padding-inline: var(--_pane-spacing);
    padding-block: var(--_pane-spacing) calc(var(--_pane-spacing) * 1.5);
  }

  .pane__footer {
    background-color: var(--_bg-color);
    border-top: 1px solid var(--c-color-neutral-border-quiet);
    padding-inline: var(--_pane-spacing);
    padding-block: calc(var(--_pane-spacing) / 2);
    position: sticky;
    inset-block-end: 0;
    //inset-inline: 0;
    //inset-block-end: 0;
    //z-index: 10;
  }

  .actions {
    display: flex;
    gap: var(--c-spacing-md);
    justify-content: end;
    align-items: center;
  }
</style>
