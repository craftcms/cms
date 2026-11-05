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
    placement="bottom-start"
    .opened="opened"
    @opened-changed="opened = $event.detail.opened"
  >
    <craft-button
      slot="invoker"
      type="button"
      variant="plain"
      size="small"
      icon="chevron-down"
      icon-position="suffix"
    >
      <craft-status
        slot="prefix"
        status="pending"
        aria-hidden="true"
      ></craft-status>
      {{ label }}
    </craft-button>

    <div
      slot="content-body"
      role="region"
      :aria-labelledby="headingId"
      class="workflow-drafts-popover"
    >
      <h2 :id="headingId">{{ t('Drafts in Review') }}</h2>
      <WorkflowDraftReviews :drafts="drafts" />
    </div>
  </craft-popover>
</template>

<style scoped>
  .workflow-drafts-popover {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .workflow-drafts-popover h2 {
    margin: 0;
    font-size: var(--c-text-base);
  }

  craft-popover::part(popup) {
    width: min(18rem, calc(100vw - 2rem));
    max-width: none;
  }
</style>
