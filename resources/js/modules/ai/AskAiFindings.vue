<script setup lang="ts">
  /**
   * Lists findings: the passage each quotes, if any, the problem, and a button
   * to ask the model to fix it, unless a change already does.
   */
  import {t} from '@craftcms/ui';
  import type {AskAiFinding} from './ask-ai';

  withDefaults(
    defineProps<{
      findings: AskAiFinding[];
      /** Whether to offer “Ask to fix”, which a change's own findings don't need. */
      fixable?: boolean;
    }>(),
    {fixable: true}
  );

  const emit = defineEmits<{
    askToFix: [findings: AskAiFinding[]];
  }>();
</script>

<template>
  <ul class="ask-ai-findings">
    <li v-for="(finding, index) in findings" :key="index">
      <blockquote v-if="finding.quote" class="ask-ai-findings__quote">
        {{ finding.quote }}
      </blockquote>
      <p>{{ finding.issue }}</p>
      <div v-if="fixable">
        <craft-button
          type="button"
          variant="outline"
          size="small"
          @click="emit('askToFix', [finding])"
        >
          {{ t('Ask to fix') }}
          <span class="sr-only">{{ finding.issue }}</span>
        </craft-button>
      </div>
    </li>
  </ul>
</template>

<style scoped>
  .ask-ai-findings {
    display: flex;
    flex-direction: column;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: var(--c-text-base);
    overflow-wrap: anywhere;
  }

  .ask-ai-findings li {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .ask-ai-findings li:first-child {
    padding-block-start: 0;
    border-block-start: 0;
  }

  .ask-ai-findings li:last-child {
    padding-block-end: 0;
  }

  .ask-ai-findings p {
    margin: 0;
  }

  .ask-ai-findings__quote {
    margin: 0;
    padding-inline-start: var(--c-spacing-md);
    border-inline-start: 3px solid var(--c-color-accent-border-normal);
    color: var(--c-color-neutral-on-quiet);
    font-size: var(--c-text-sm);
    white-space: pre-line;
  }
</style>
