<script setup lang="ts">
  /**
   * The filter bar's pill: one or more `.filter-pill__segment` buttons, then
   * one that removes the lot.
   */
  defineProps<{
    label: string;
    removeLabel: string;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{remove: []}>();
</script>

<template>
  <span class="filter-pill" role="group" :aria-label="label">
    <slot />
    <button
      type="button"
      class="filter-pill__remove"
      :aria-label="removeLabel"
      :disabled="disabled"
      @click.stop="emit('remove')"
    >
      <craft-icon name="xmark"></craft-icon>
    </button>
  </span>
</template>

<!-- Not scoped: segments come from other components, some inside popovers. -->
<style lang="scss">
  .filter-pill {
    display: inline-flex;
    align-items: stretch;
    max-width: 100%;
    height: var(--c-size-control-sm);
    overflow: hidden;
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-full);
    background-color: var(--c-surface-default);
  }

  .filter-pill__segment,
  .filter-pill__remove {
    display: inline-flex;
    align-items: center;
    padding-block: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    white-space: nowrap;
    cursor: pointer;

    &:hover:not(:disabled) {
      background-color: var(--c-color-neutral-fill-quiet);
    }
  }

  .filter-pill__segment {
    gap: 0.35em;
    min-width: 0;
    height: 100%;
    padding-inline: var(--c-spacing-md);
    border-inline-end: 1px solid var(--c-color-neutral-border-quiet);

    &[aria-expanded='true'] {
      background-color: var(--c-color-neutral-fill-quiet);
    }
  }

  // The pill's rounded end takes room from the right, so the icon sits a
  // little left of the box's center to look centered.
  .filter-pill__remove {
    padding-inline: calc(var(--c-spacing-sm) - 1px)
      calc(var(--c-spacing-sm) + 2px);
    opacity: 0.7;

    &:hover:not(:disabled) {
      opacity: 1;
    }
  }
</style>
