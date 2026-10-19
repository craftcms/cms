<script setup lang="ts">
  /**
   * A field's current and proposed text as one word-level diff. See
   * `renderDiff()` for how it collapses and highlights. Text for an empty
   * field is shown as is.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {renderDiff} from '@/common/utils/diff';

  const props = defineProps<{
    current: string;
    proposed: string;
    /** Passages of the current text to highlight. */
    quotes?: string[];
    /** Shows the whole text rather than just the changes. */
    expanded?: boolean;
  }>();

  const pieces = computed(
    () =>
      renderDiff(props.current, props.proposed, props.quotes, props.expanded)
        .pieces
  );
</script>

<template>
  <p v-if="!current" class="ask-ai-diff" v-text="proposed"></p>
  <p v-else class="ask-ai-diff">
    <template v-for="(piece, index) in pieces" :key="index">
      <span v-if="piece.kind === 'gap'" class="ask-ai-diff__gap">
        <span aria-hidden="true" v-text="piece.text"></span>
        <span class="sr-only" v-text="t('Unchanged text')"></span>
      </span>
      <del v-else-if="piece.kind === 'delete'">
        <span class="sr-only" v-text="`${t('Deleted:')} `"></span>
        <mark v-if="piece.marked" v-text="piece.text"></mark>
        <span v-else v-text="piece.text"></span>
      </del>
      <ins v-else-if="piece.kind === 'insert'">
        <span class="sr-only" v-text="`${t('Inserted:')} `"></span>
        <span v-text="piece.text"></span>
      </ins>
      <mark v-else-if="piece.marked" v-text="piece.text"></mark>
      <span v-else v-text="piece.text"></span>
    </template>
  </p>
</template>

<style scoped>
  .ask-ai-diff {
    margin: 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    line-height: 1.6;
  }

  .ask-ai-diff del,
  .ask-ai-diff ins {
    border-radius: var(--c-radius-sm);
    padding-inline: 0.125em;
  }

  .ask-ai-diff del {
    background: var(--c-color-danger-fill-quiet);
    color: var(--c-color-danger-on-quiet);
    text-decoration: line-through;
  }

  .ask-ai-diff ins {
    background: var(--c-color-success-fill-quiet);
    color: var(--c-color-success-on-quiet);
    text-decoration: underline;
  }

  .ask-ai-diff del + ins {
    margin-inline-start: 0.25em;
  }

  .ask-ai-diff mark {
    background: none;
    color: inherit;
    text-decoration: underline wavy var(--c-color-warning-border-normal);
    text-decoration-skip-ink: none;
    text-underline-offset: 0.25em;
  }

  .ask-ai-diff__gap {
    padding-inline: 0.25em;
  }
</style>
