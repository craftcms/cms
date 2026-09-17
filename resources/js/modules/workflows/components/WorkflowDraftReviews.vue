<script setup lang="ts">
  import {t} from '@craftcms/ui';

  defineProps<{
    drafts: CraftCms.Cms.Workflow.Data.WorkflowDraftReviewData[];
  }>();
</script>

<template>
  <ul class="workflow-drafts">
    <li v-for="draft in drafts" :key="draft.url" class="workflow-draft">
      <div class="workflow-draft__details">
        <div class="workflow-draft__heading">
          <strong>{{ draft.name }}</strong>
          <span class="workflow-draft__status">
            <craft-status
              :status="draft.statusIndicator"
              :label="draft.statusLabel"
            ></craft-status>
            {{ draft.statusLabel }}
          </span>
        </div>
        <p>
          {{
            t('Requested by {requester} for {stage}.', {
              requester: draft.requester,
              stage: draft.stage,
            })
          }}
        </p>
      </div>
      <craft-button :href="draft.url" variant="outline" size="small">
        {{ t('View draft') }}
      </craft-button>
    </li>
  </ul>
</template>

<style scoped>
  .workflow-drafts {
    display: grid;
    gap: var(--c-spacing-sm);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .workflow-draft {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-xs);
  }

  .workflow-draft + .workflow-draft {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .workflow-draft__details {
    display: grid;
    flex: 1 1 14rem;
    gap: var(--c-spacing-xs);
    min-width: 0;
  }

  .workflow-draft__heading,
  .workflow-draft__status {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .workflow-draft__heading {
    flex-wrap: wrap;
  }

  .workflow-draft__status,
  .workflow-draft p {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .workflow-draft p {
    margin: 0;
  }
</style>
