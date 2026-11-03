<script setup lang="ts">
   /**
   * Something a person wrote, in a bubble tinted with their photo’s colors:
   * a prompt in the AI assistant’s conversation, or a comment in a timeline.
   * Pass `usePhotoColors()` as its `style`.
   *
   * Text at its top or bottom looks as far from the edge as text at its sides,
   * and a code block, quote, table or figure there sits as far from the edge
   * as one at its sides.
   */
</script>

<template>
  <div class="message-bubble">
    <slot />
  </div>
</template>

<style scoped>
  .message-bubble {
    padding-block: calc(var(--c-spacing-md) - var(--c-spacing-xs));
    padding-inline: var(--c-spacing-md);
    border: 1px solid
      color-mix(in srgb, var(--c-color-neutral-border-quiet) 50%, transparent);
    border-radius: var(--c-radius-xl);
    background-color: var(--c-color-neutral-fill-quiet);
    background-image: linear-gradient(
      to top right,
      color-mix(
        in srgb,
        var(--photo-color-start, transparent) 15%,
        transparent
      ),
      color-mix(in srgb, var(--photo-color-end, transparent) 15%, transparent)
    );
    box-shadow: var(--c-shadow-sm);
    color: var(--c-color-neutral-on-quiet);
    overflow-wrap: anywhere;

    & > :deep(.markdown-html) {
      text-box: trim-both cap alphabetic;
    }

    & > :deep(* > :is(pre, blockquote, table, figure):first-child) {
      margin-block-start: var(--c-spacing-xs);
    }

    & > :deep(* > :is(pre, blockquote, table, figure):last-child) {
      margin-block-end: var(--c-spacing-xs);
    }
  }
</style>
