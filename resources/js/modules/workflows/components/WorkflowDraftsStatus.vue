<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, ref, useId} from 'vue';
  import WorkflowDraftReviews from './WorkflowDraftReviews.vue';

  const props = defineProps<{
    drafts: CraftCms.Cms.Workflow.Data.WorkflowDraftReviewData[];
  }>();

  const opened = ref(false);
  const headingId = `workflow-drafts-${useId()}`;
  const label = computed(() =>
    props.drafts.length === 1
      ? t('1 draft in review')
      : t('{count} drafts in review', {count: props.drafts.length})
  );
</script>

<template>
  <craft-popover
    placement="bottom-end"
    :opened="opened"
    @opened-changed="opened = $event.detail.opened"
  >
    <button
      slot="invoker"
      type="button"
      class="workflow-drafts-status"
      :aria-expanded="opened"
    >
      <craft-status status="pending" :label="label"></craft-status>
      <span>{{ label }}</span>
      <craft-icon name="chevron-down" aria-hidden="true"></craft-icon>
    </button>

    <div
      slot="content-body"
      role="region"
      :aria-labelledby="headingId"
      class="workflow-drafts-popover"
    >
      <h2 :id="headingId">{{ t('Drafts in review') }}</h2>
      <WorkflowDraftReviews :drafts="drafts" />
    </div>
  </craft-popover>
</template>

<style scoped>
  .workflow-drafts-status {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    min-block-size: 2rem;
    padding: var(--c-spacing-xs);
    border: 0;
    border-radius: var(--c-radius-md);
    background: transparent;
    color: var(--c-text-quiet);
    cursor: pointer;
    font: inherit;
    white-space: nowrap;
  }

  .workflow-drafts-status:hover {
    background: var(--c-color-neutral-fill-quiet);
    color: var(--c-text);
  }

  .workflow-drafts-status:focus-visible {
    outline: 2px solid var(--c-color-focus-ring);
    outline-offset: 2px;
  }

  .workflow-drafts-status > craft-icon {
    transition: transform 100ms ease;
  }

  .workflow-drafts-status[aria-expanded='true'] > craft-icon {
    transform: rotate(180deg);
  }

  .workflow-drafts-popover {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .workflow-drafts-popover h2 {
    margin: 0;
    font-size: var(--c-text-base);
  }

  craft-popover::part(popup) {
    width: min(24rem, calc(100vw - 2rem));
    max-width: none;
  }
</style>
