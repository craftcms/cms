<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowRun = WorkflowReviewData['runs'][number];
  type WorkflowStage = WorkflowRun['stages'][number];

  const props = defineProps<{
    run: WorkflowRun;
    stage: WorkflowStage;
    approvalsRequired: number;
    approvals: number;
    reviewers: Array<{name: string}>;
  }>();

  const remainingReviewers = computed(() => {
    if (!props.run.current) {
      return [];
    }

    const reviewerNames = new Set(
      props.stage.events
        .filter((event) => event.decision !== null)
        .map((event) => event.actor.label)
    );

    return props.reviewers.filter(
      (reviewer) => !reviewerNames.has(reviewer.name)
    );
  });
</script>

<template>
  <craft-card class="workflow-user-review-summary">
    <span slot="label">
      {{
        t('{count} of {required} approved', {
          count: approvals,
          required: approvalsRequired,
        })
      }}
    </span>
    <ul
      v-if="remainingReviewers.length"
      class="workflow-user-review-summary__reviewers"
    >
      <li
        v-for="reviewer in remainingReviewers"
        :key="reviewer.name"
        class="workflow-user-review-summary__reviewer-row"
      >
        <span class="workflow-user-review-summary__reviewer">
          <craft-avatar :label="reviewer.name" />
          <span class="workflow-user-review-summary__name">
            {{ reviewer.name }}
          </span>
        </span>
        <craft-badge fill="orange" size="small">
          {{ t('Pending') }}
        </craft-badge>
      </li>
    </ul>
  </craft-card>
</template>

<style scoped>
  .workflow-user-review-summary {
    margin-inline: var(--c-spacing-xs);
  }

  .workflow-user-review-summary__reviewers {
    display: grid;
    gap: var(--c-spacing-xs);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .workflow-user-review-summary__reviewer-row,
  .workflow-user-review-summary__reviewer {
    display: flex;
    align-items: center;
  }

  .workflow-user-review-summary__reviewer-row {
    justify-content: space-between;
    gap: var(--c-spacing-sm);
  }

  .workflow-user-review-summary__reviewer {
    min-width: 0;
    gap: var(--c-spacing-xs);
  }

  .workflow-user-review-summary__reviewer craft-avatar {
    --size: var(--c-size-icon-lg);
    flex: none;
  }

  .workflow-user-review-summary__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .workflow-user-review-summary craft-badge {
    flex: none;
  }
</style>
