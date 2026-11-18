<script setup lang="ts">
  /**
   * Craft's edition, as an outlined badge. It reads as “{edition} edition” to
   * screen readers, which starts with the badge's visible text.
   */
  import {t} from '@craftcms/ui';

  defineProps<{
    edition: string;
  }>();
</script>

<template>
  <span class="edition-badge">
    <span aria-hidden="true">{{ edition }}</span
    >{{ ' ' }}
    <span class="sr-only">{{ t('{edition} edition', {edition}) }}</span>
  </span>
</template>

<style scoped>
  .edition-badge {
    display: inline-flex;
    padding-block: 3px;
    padding-inline: 5px 3px;
    border: 1px solid color-mix(in srgb, currentColor 70%, transparent);
    color: var(--c-text-quiet);
    border-radius: var(--c-radius-sm);
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    user-select: none;
  }
</style>
