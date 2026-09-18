<script setup lang="ts">
  import {t} from '@craftcms/ui';

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

  defineProps<{
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
        v-if="approvedReviewers?.length"
        class="workflow-user-review-summary__reviewers"
      >
        <li
          v-for="reviewer in approvedReviewers"
          :key="reviewer.name"
          class="workflow-user-review-summary__reviewer-row"
        >
          <span class="workflow-user-review-summary__reviewer">
            <craft-avatar :label="reviewer.name" />
            <span class="workflow-user-review-summary__name">
              {{ reviewer.name }}
            </span>
          </span>
          <craft-badge fill="green" size="small">
            {{ t('Approved') }}
          </craft-badge>
        </li>
      </ul>
      <ul
        v-if="carriedReviewers?.length"
        class="workflow-user-review-summary__reviewers"
      >
        <li
          v-for="reviewer in carriedReviewers"
          :key="reviewer.name"
          class="workflow-user-review-summary__reviewer-row"
        >
          <span class="workflow-user-review-summary__reviewer">
            <craft-avatar :label="reviewer.name" />
            <span class="workflow-user-review-summary__name">
              {{ reviewer.name }}
            </span>
          </span>
          <craft-badge fill="green" size="small">
            {{ t('Approved previously') }}
          </craft-badge>
        </li>
      </ul>
      <ul
        v-if="remainingReviewers?.length"
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
          v-if="group.approvedReviewers?.length"
          class="workflow-user-review-summary__reviewers"
        >
          <li
            v-for="reviewer in group.approvedReviewers"
            :key="reviewer.name"
            class="workflow-user-review-summary__reviewer-row"
          >
            <span class="workflow-user-review-summary__reviewer">
              <craft-avatar :label="reviewer.name" />
              <span class="workflow-user-review-summary__name">
                {{ reviewer.name }}
              </span>
            </span>
            <craft-badge fill="green" size="small">
              {{ t('Approved') }}
            </craft-badge>
          </li>
        </ul>
        <ul
          v-if="group.carriedReviewers.length"
          class="workflow-user-review-summary__reviewers"
        >
          <li
            v-for="reviewer in group.carriedReviewers"
            :key="reviewer.name"
            class="workflow-user-review-summary__reviewer-row"
          >
            <span class="workflow-user-review-summary__reviewer">
              <craft-avatar :label="reviewer.name" />
              <span class="workflow-user-review-summary__name">
                {{ reviewer.name }}
              </span>
            </span>
            <craft-badge fill="green" size="small">
              {{ t('Approved previously') }}
            </craft-badge>
          </li>
        </ul>
        <ul
          v-if="group.remainingReviewers.length"
          class="workflow-user-review-summary__reviewers"
        >
          <li
            v-for="reviewer in group.remainingReviewers"
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
