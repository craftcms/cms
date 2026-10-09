<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowRun = WorkflowReviewData['runs'][number];
  type WorkflowStage = WorkflowRun['stages'][number];
  type Reviewer = {name: string};
  type Group = {
    name: string;
    approvals: number;
    approvedReviewers?: Reviewer[];
    carriedReviewers: Reviewer[];
    remainingReviewers: Reviewer[];
  };

  type ReviewerSection = {
    reviewers: Reviewer[];
    label: string;
    fill: 'green' | 'orange';
  };

  const props = defineProps<{
    run: WorkflowRun;
    stage: WorkflowStage;
    approvalMode: 'total' | 'per-group';
    approvalsRequired: number;
    approvals?: number;
    approvedReviewers?: Reviewer[];
    carriedReviewers?: Reviewer[];
    remainingReviewers?: Reviewer[];
    groups?: Group[];
  }>();

  function reviewerSections(
    approved: Reviewer[] | undefined,
    carried: Reviewer[] | undefined,
    remaining: Reviewer[] | undefined
  ): ReviewerSection[] {
    return [
      {reviewers: approved ?? [], label: t('Approved'), fill: 'green'},
      {
        reviewers: carried ?? [],
        label: t('Approved previously'),
        fill: 'green',
      },
      {reviewers: remaining ?? [], label: t('Pending'), fill: 'orange'},
    ];
  }

  const totalReviewerSections = computed(() =>
    reviewerSections(
      props.approvedReviewers,
      props.carriedReviewers,
      props.remainingReviewers
    )
  );
</script>

<template>
  <craft-card class="workflow-user-review-summary">
    <template v-if="approvalMode === 'total'">
      <span slot="label">
        {{
          t('{count} of {required} approved', {
            count: approvals ?? 0,
            required: approvalsRequired,
          })
        }}
      </span>
      <ul
        v-for="section in totalReviewerSections"
        v-show="section.reviewers.length"
        :key="section.label"
        class="workflow-user-review-summary__reviewers"
      >
        <li
          v-for="reviewer in section.reviewers"
          :key="reviewer.name"
          class="workflow-user-review-summary__reviewer-row"
        >
          <span class="workflow-user-review-summary__reviewer">
            <craft-avatar :label="reviewer.name" />
            <span class="workflow-user-review-summary__name">
              {{ reviewer.name }}
            </span>
          </span>
          <craft-badge :fill="section.fill" size="small">
            {{ section.label }}
          </craft-badge>
        </li>
      </ul>
    </template>
    <ul v-else class="workflow-user-review-summary__groups">
      <li
        v-for="group in groups ?? []"
        :key="group.name"
        class="workflow-user-review-summary__group"
      >
        <span class="workflow-user-review-summary__group-label">{{
          group.name
        }}</span>
        <span>
          {{
            t('{count} of {required} approved', {
              count: group.approvals,
              required: approvalsRequired,
            })
          }}
        </span>
        <ul
          v-for="section in reviewerSections(
            group.approvedReviewers,
            group.carriedReviewers,
            group.remainingReviewers
          )"
          v-show="section.reviewers.length"
          :key="section.label"
          class="workflow-user-review-summary__reviewers"
        >
          <li
            v-for="reviewer in section.reviewers"
            :key="reviewer.name"
            class="workflow-user-review-summary__reviewer-row"
          >
            <span class="workflow-user-review-summary__reviewer">
              <craft-avatar :label="reviewer.name" />
              <span class="workflow-user-review-summary__name">
                {{ reviewer.name }}
              </span>
            </span>
            <craft-badge :fill="section.fill" size="small">
              {{ section.label }}
            </craft-badge>
          </li>
        </ul>
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

  .workflow-user-review-summary__groups {
    display: grid;
    gap: var(--c-spacing-md);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .workflow-user-review-summary__group {
    display: grid;
    gap: var(--c-spacing-xs);
  }

  .workflow-user-review-summary__group-label {
    font-weight: var(--c-font-weight-semibold);
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
