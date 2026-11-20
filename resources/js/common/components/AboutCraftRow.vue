<script setup lang="ts">
  /**
   * A row of the “About Craft CMS” UI: an icon, a line with a quieter detail
   * below it, and an optional action, such as a button.
   */
  defineProps<{
    /** `accent` tints the row, for something that needs attention. */
    tone?: 'accent';
  }>();
</script>

<template>
  <div class="about-craft-row" :data-tone="tone">
    <span class="about-craft-row__icon cp-item-icon"><slot name="icon" /></span>
    <div class="about-craft-row__body">
      <slot />
    </div>
    <div v-if="$slots.action" class="about-craft-row__action">
      <slot name="action" />
    </div>
  </div>
</template>

<style scoped>
  .about-craft-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    /* Spaced like EntryList's thumbnails from their text, and the About
       widget's mark from Craft's name */
    gap: var(--c-spacing-xs) var(--c-spacing-md);
    /* Its padding reaches outside, so its content lines up with what's
       around it, and a tint extends past it. */
    margin-inline: calc(var(--c-spacing-sm) * -1);
    padding: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);

    /* Blue, unless a container gives it a color of its own with
       --about-craft-tint-fill, -on and -icon, such as a tinted card's */
    &[data-tone='accent'] {
      --c-color-fill-quiet: var(
        --about-craft-tint-fill,
        var(--c-color-accent-fill-quiet)
      );
      --c-color-on-quiet: var(
        --about-craft-tint-on,
        var(--c-color-accent-on-quiet)
      );
      --c-text-link: var(--c-color-on-quiet);
      background-color: var(--c-color-fill-quiet);
      color: var(--c-color-on-quiet);
    }
  }

  .about-craft-row__icon {
    display: flex;
    flex: none;
    align-self: start;
    align-items: center;
    /* As wide as the icon's glyph, so the text starts right after it */
    inline-size: var(--c-size-icon-sm);
    block-size: 1lh;
  }

  /* Matching its fill, rather than the card's color */
  [data-tone='accent'] > .about-craft-row__icon {
    color: var(--about-craft-tint-icon, var(--c-color-accent-fill-loud));
  }

  /* An icon is a quarter wider than its glyph, centered in it, so its glyph
     is pulled back to the start of the row. */
  .about-craft-row__icon :slotted(craft-icon) {
    flex: none;
    margin-inline-start: calc((1em - 1.25em) / 2);
  }

  .about-craft-row__icon :slotted(craft-spinner) {
    --size: var(--c-size-icon-sm);
  }

  .about-craft-row__body {
    flex: 1 1 10rem;
    min-inline-size: 0;
  }

  /* A line tall, with its button overflowing it evenly, so a button a little
     taller than the text doesn't push the text below the icon. */
  .about-craft-row__action {
    display: flex;
    flex: none;
    align-items: center;
    block-size: 1lh;
    margin-inline-start: auto;
  }

  :slotted(.about-craft-row__title) {
    display: block;
    font-weight: 500;
    text-wrap: pretty;
  }

  :slotted(.about-craft-row__detail) {
    display: block;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  /* A row's next step, such as “Buy now”: blue like a link, with an arrow. As
     an [inherit] plain button, its hover fill follows. */
  :slotted(.about-craft-row__link) {
    color: var(--c-text-link);
    /* A circle reads smaller than the refresh button's arrows at the same size. */
    --c-icon-size: var(--c-size-icon-md);
    /* Its icon centered under the refresh button's, which is that much
       smaller. Moved rather than margined, which would undo its flush. */
    translate: calc((var(--c-size-icon-md) - var(--c-size-icon-sm)) / 2) 0;
  }

  /* A plain button's hover fill is made for on-quiet text, not the link color. */
  :slotted(.about-craft-row__link:not([variant~='link']):active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
  }

  @media (hover: hover) {
    :slotted(.about-craft-row__link:not([variant~='link']):hover) {
      color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    }
  }

  [data-tone='accent'] :slotted(.about-craft-row__detail) {
    color: inherit;
  }
</style>
