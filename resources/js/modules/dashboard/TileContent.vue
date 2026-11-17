<script setup lang="ts">
  /**
   * A centered icon, label, and quieter description, as dashboard widgets'
   * tiles and status messages show them. Content in the default slot, such
   * as a button, goes below.
   *
   * The icon takes the text color. Set `--tile-content-icon-color` to give it
   * another, such as `var(--c-color-fill-loud)` for the card's color.
   */
  defineProps<{
    icon?: string | null;
    label: string;
    description?: string | null;
  }>();

  defineSlots<{
    /** In place of the `description` prop. */
    description?: () => unknown;
    default?: () => unknown;
  }>();
</script>

<template>
  <span class="tile-content">
    <craft-icon
      v-if="icon"
      :name="icon"
      class="tile-content__icon"
    ></craft-icon>
    <span class="tile-content__label">{{ label }}</span>
    <template v-if="description || $slots.description"
      ><span class="sr-only">, </span
      ><span class="tile-content__description"
        ><slot name="description">{{ description }}</slot></span
      ></template
    >
    <slot />
  </span>
</template>

<style scoped>
  .tile-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    white-space: normal;
  }

  .tile-content__icon {
    --c-icon-size: calc(28rem / 16);
    margin-block-end: var(--c-spacing-sm);
    color: var(--tile-content-icon-color, currentColor);
  }

  .tile-content__label {
    font-weight: 600;
  }

  /* In the label's color, a little lighter. */
  .tile-content__description {
    font-size: var(--c-text-sm);
    opacity: 0.75;
  }
</style>
