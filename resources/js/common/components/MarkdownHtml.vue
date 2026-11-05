<script setup lang="ts">
  /**
   * Markdown rendered as HTML, such as a comment or a message to the AI
   * assistant, styled the same wherever it shows, with its fenced code
   * highlighted. The HTML must already be safe.
   */
  import {useTemplateRef, watchPostEffect} from 'vue';
  import {highlightCodeBlocks} from '@/common/utils/highlight-code';

  const props = defineProps<{html: string | null}>();
  const root = useTemplateRef<HTMLElement>('root');

  watchPostEffect(() => {
    if (root.value && props.html) {
      highlightCodeBlocks(root.value);

      // Code blocks are dark in either theme, so their text and highlighting take the dark theme's colors.
      for (const pre of root.value.querySelectorAll('pre')) {
        pre.dataset.theme = 'dark';
      }
    }
  });
</script>

<template>
  <div ref="root" class="prose markdown-html" v-html="html"></div>
</template>

<style scoped>
  .markdown-html {
    --_code-block-bg: var(--c-color-black-fill-quiet);
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    min-inline-size: 0;
    overflow-wrap: anywhere;
  }

  .markdown-html :deep(:is(h1, h2)) {
    margin: 0;
    font-size: var(--c-text-lg);
    font-weight: 600;
  }

  .markdown-html :deep(:is(h3, h4, h5, h6)) {
    margin: 0;
    font-size: var(--c-text-base);
    font-weight: 600;
  }

  .markdown-html :deep(:is(ul, ol, blockquote)) {
    margin: 0;
  }

  .markdown-html :deep(pre) {
    overflow-x: auto;
    padding: var(--c-spacing-md);
    border-radius: var(--c-radius-md);
    background-color: var(--_code-block-bg);
    overflow-wrap: normal;
    text-box: trim-both cap alphabetic;
  }

  .markdown-html :deep(code) {
    font-family: var(--c-font-mono);
    font-size: 0.9em;
  }

  .markdown-html :deep(:not(pre) > code) {
    padding: 0;
    background-color: transparent;
    color: var(--c-color-red-on-quiet);
  }

  .markdown-html :deep(pre code) {
    font-size: inherit;
  }

  .markdown-html :deep(img) {
    max-inline-size: 100%;
    block-size: auto;
  }

  .markdown-html :deep(table) {
    display: block;
    overflow-x: auto;
    border-collapse: collapse;
    overflow-wrap: normal;
  }

  .markdown-html :deep(th),
  .markdown-html :deep(td) {
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-quiet);
    text-align: start;
  }
</style>
