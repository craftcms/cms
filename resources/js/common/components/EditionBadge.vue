<script setup lang="ts">
  /**
   * Craft's edition, as an outlined badge. It reads as “{edition} edition” to
   * screen readers, which starts with the badge's visible text.
   */
  import {t} from '@craftcms/ui';

  defineProps<{
    edition: string;
    /** `large` sets it at an h3's size, such as to head a card. */
    size?: 'large';
  }>();
</script>

<template>
  <!-- Its padding is uneven to offset the letter spacing after the last
    letter, which follows the edition name's own direction, not the page's -->
  <span
    class="edition-badge"
    :class="{'edition-badge--large': size === 'large'}"
    dir="auto"
  >
    <span class="edition-badge__label" aria-hidden="true">{{ edition }}</span
    >{{ ' ' }}
    <span class="sr-only">{{ t('{edition} edition', {edition}) }}</span>
  </span>
</template>

<style scoped>
  .edition-badge {
    display: inline-flex;
    padding-block: 0.3em;
    padding-inline: 0.5em 0.3em;
    border: 1px solid color-mix(in srgb, currentColor 70%, transparent);
    border-radius: 0.3em;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    user-select: none;
  }

  /* Centered on its capitals, padded back out to a full line, as
     trimmedTextBoxStyles does in @craftcms/ui. */
  @supports (text-box: trim-both cap alphabetic) {
    .edition-badge__label {
      text-box: trim-both cap alphabetic;
      padding-block: calc((1lh - 1cap) / 2);
    }
  }

  .edition-badge--large {
    font-size: var(--c-text-md);
  }
</style>
