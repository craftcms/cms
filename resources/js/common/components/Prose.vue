<script setup lang="ts">
   /** Long-form text, such as rendered Markdown, set the way Craft 5's `.readable` set it. */
</script>

<template>
  <div class="cp-prose">
    <slot></slot>
  </div>
</template>

<style scoped>
  .cp-prose {
    --cp-prose-alert-icon: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>');
    --cp-prose-tip-icon: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M272 384c9.6-31.9 29.5-59.1 49.2-86.2c5.2-7.1 10.4-14.2 15.4-21.4c19.8-28.5 31.4-63 31.4-100.3C368 78.8 289.2 0 192 0S16 78.8 16 176c0 37.3 11.6 71.9 31.4 100.3c5 7.2 10.2 14.3 15.4 21.4c19.8 27.1 39.7 54.4 49.2 86.2l160 0zM192 512c44.2 0 80-35.8 80-80l0-16-160 0 0 16c0 44.2 35.8 80 80 80zM112 176c0 8.8-7.2 16-16 16s-16-7.2-16-16c0-61.9 50.1-112 112-112c8.8 0 16 7.2 16 16s-7.2 16-16 16c-44.2 0-80 35.8-80 80z"/></svg>');

    font-size: 1rem;
    line-height: 1.375;
  }

  .cp-prose :deep(p) {
    margin-block: 1em;
    margin-inline: 0;
  }

  .cp-prose :deep(:is(h5, .h5) + p) {
    margin-block-start: 0;
  }

  .cp-prose :deep(:is(h1, .h1, h2, .h2, h3, .h3, h4, .h4, h5, .h5, h6, .h6)) {
    margin-block: 1.5rem 1rem;
    margin-inline: 0;
    font-weight: 600;
  }

  .cp-prose :deep(:is(h1, .h1)) {
    font-size: 2rem;
    line-height: 1.25;
    color: var(--c-text-default);
  }

  .cp-prose :deep(:is(h2, .h2)) {
    font-size: 1.5rem;
    line-height: 1.25;
  }

  .cp-prose :deep(:is(h3, .h3)) {
    font-size: 1.25rem;
    line-height: 1.2;
  }

  .cp-prose :deep(:is(h4, .h4)) {
    font-size: 1rem;
    line-height: 1.25;
  }

  .cp-prose :deep(:is(h5, .h5)) {
    font-size: 0.875rem;
    line-height: 1.2857;
  }

  .cp-prose :deep(:is(h6, .h6)) {
    font-size: 0.85rem;
    line-height: 1.25;
    color: var(--c-text-quiet);
  }

  .cp-prose :deep(:is(ul, ol)) {
    margin-block: 1em;
    margin-inline: 0;
    padding-inline-start: 2em;
  }

  .cp-prose :deep(ul li) {
    list-style-type: disc;
  }

  .cp-prose :deep(ol li) {
    list-style-type: decimal;
  }

  .cp-prose :deep(li + li) {
    margin-block-start: 0.25em;
  }

  .cp-prose :deep(blockquote) {
    margin-block: 1rem;
    margin-inline: 0;
  }

  .cp-prose :deep(blockquote:not(.note)) {
    padding-block: 0;
    padding-inline: 1rem;
    color: var(--c-text-quiet);
    border-inline-start: 4px solid var(--c-color-border-quiet);
  }

  .cp-prose :deep(blockquote.note) {
    --note-icon: var(--cp-prose-alert-icon);

    position: relative;
    padding: 1em;
    padding-inline-start: 3.125rem;
    border: 1px solid var(--c-color-danger-border-loud);
    border-radius: var(--c-radius-sm);
    color: var(--c-color-danger-on-quiet);
  }

  .cp-prose :deep(blockquote.note.tip) {
    --note-icon: var(--cp-prose-tip-icon);

    border-color: var(--c-color-accent-border-loud);
    color: var(--c-color-accent-on-quiet, var(--c-color-accent-fill-loud));
  }

  .cp-prose :deep(blockquote.note.warning) {
    border-color: var(--c-color-warning-border-loud);
    color: var(--c-color-warning-on-quiet);
  }

  .cp-prose :deep(blockquote.note::before) {
    position: absolute;
    inset-block-start: 1rem;
    inset-inline-start: 1rem;
    inline-size: 1.5rem;
    block-size: 1.5rem;
    background-color: currentColor;
    content: '';
    mask: var(--note-icon) center / contain no-repeat;
  }

  .cp-prose :deep(blockquote.note a[href]) {
    color: currentColor;
    text-decoration: underline;
  }

  .cp-prose :deep(table) {
    display: block;
    overflow: auto;
    inline-size: 100%;
    border-collapse: collapse;
  }

  .cp-prose :deep(:is(td, th)) {
    padding-block: 0.375rem;
    padding-inline: 0.8125rem;
    border: 1px solid var(--c-color-border-quiet);
  }

  .cp-prose :deep(th) {
    font-weight: 600;
  }

  .cp-prose :deep(tr:nth-child(2n)) {
    background-color: var(--c-surface-sunken);
  }

  .cp-prose :deep(table img) {
    background-color: transparent;
  }

  @media (forced-colors: active) {
    .cp-prose :deep(blockquote.note::before) {
      forced-color-adjust: none;
      background-color: CanvasText;
    }
  }
</style>
