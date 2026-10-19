<script setup lang="ts">
  /** Lists findings, each with the passage it quotes, if any. */
  import type {AskAiFinding} from './ask-ai';

  defineProps<{
    findings: AskAiFinding[];
  }>();

  /** Shortens a long quote; the full passage is highlighted in the diff. */
  function excerpt(quote: string): string {
    return quote.length > 80 ? `${quote.slice(0, 77).trimEnd()}…` : quote;
  }
</script>

<template>
  <ul class="ask-ai-findings">
    <li v-for="(finding, index) in findings" :key="index">
      <craft-icon name="flag" class="ask-ai-findings__icon"></craft-icon>
      <div>
        <q v-if="finding.quote" class="ask-ai-findings__quote">{{
          excerpt(finding.quote)
        }}</q>
        <span>{{ finding.issue }}</span>
      </div>
    </li>
  </ul>
</template>

<style scoped>
  .ask-ai-findings {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: var(--c-text-base);
  }

  .ask-ai-findings li {
    display: flex;
    gap: var(--c-spacing-sm);
  }

  .ask-ai-findings__icon {
    flex: none;
    margin-block-start: 0.2em;
    color: var(--c-color-warning-on-quiet);
  }

  .ask-ai-findings__quote {
    display: block;
    margin-block-end: var(--c-spacing-xs);
    color: var(--c-color-neutral-on-quiet);
    font-style: italic;
  }
</style>
