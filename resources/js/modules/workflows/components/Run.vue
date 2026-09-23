<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/disclosure/disclosure';
  import Stage from './Stage.vue';
  import TimelineItem from './TimelineItem.vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowRun = WorkflowReviewData['runs'][number];

  const props = defineProps<{
    run: WorkflowRun;
    number: number;
    collapsible: boolean;
  }>();
</script>

<template>
  <craft-pane appearance="outline" padding="sm">
    <component
      :is="collapsible ? 'craft-disclosure' : 'section'"
      class="workflow-review-run"
      :opened.prop="collapsible ? run.current : undefined"
    >
      <craft-button
        v-if="collapsible"
        slot="invoker"
        type="button"
        variant="plain"
        align="start"
        class="workflow-review-run__heading"
      >
        <span class="workflow-review-run__heading-content">
          <strong>{{ t('Review {number}', {number}) }}</strong>
          <span class="workflow-review-run__status">
            <craft-status
              :status="run.statusIndicator"
              :label="run.statusLabel"
            ></craft-status>
            {{ run.statusLabel }}
            <craft-icon
              class="workflow-review-run__chevron"
              name="chevron-down"
            />
          </span>
        </span>
      </craft-button>

      <div
        :slot="collapsible ? 'content' : undefined"
        class="workflow-review-run__content"
      >
        <ol class="workflow-review-events workflow-review-submission">
          <li>
            <TimelineItem :event="run.submission" last />
          </li>
        </ol>

        <ol class="workflow-review-stages">
          <li
            v-for="(stage, stageIndex) in run.stages"
            :key="stageIndex"
            :aria-current="stage.current ? 'step' : undefined"
          >
            <Stage
              :run="run"
              :stage="stage"
              :number="stageIndex + 1"
              :collapsible="run.stages.length > 1"
            />
          </li>
        </ol>
      </div>
    </component>
  </craft-pane>
</template>

<style scoped>
  .workflow-review-run {
    display: block;
  }

  .workflow-review-run__heading {
    width: 100%;
    --c-button-height: var(--c-size-touch-target);
    --c-button-spacing-inline: var(--c-spacing-xs);
  }

  .workflow-review-run__heading::part(content) {
    gap: var(--c-spacing-xs);
  }

  .workflow-review-run__heading::part(label) {
    flex: 1;
    min-width: 0;
    text-align: start;
  }

  .workflow-review-run__heading-content {
    display: flex;
    width: 100%;
    align-items: center;
  }

  .workflow-review-run__status {
    display: flex;
    margin-inline-start: auto;
    align-items: center;
    gap: var(--c-spacing-xs);
    color: var(--c-text);
    font-size: var(--c-text-sm);
  }

  .workflow-review-run__chevron {
    transition: transform 100ms ease;
  }

  .workflow-review-run[opened] .workflow-review-run__chevron {
    transform: rotate(180deg);
  }

  .workflow-review-run__content,
  .workflow-review-stages,
  .workflow-review-events {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .workflow-review-stages,
  .workflow-review-events {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .workflow-review-run__content {
    margin-block-start: var(--c-spacing-xs);
    padding-block-end: var(--c-spacing-sm);
    padding-inline-start: 0;
    border-inline-start: 0;
  }

  .workflow-review-submission {
    padding-inline: var(--c-spacing-xs);
  }
</style>
