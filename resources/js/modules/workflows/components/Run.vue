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
  <craft-pane appearance="outline-fill" padding="md">
    <component
      :is="collapsible ? 'craft-disclosure' : 'section'"
      class="workflow-review-run"
      :appearance="collapsible ? 'plain' : undefined"
      :opened.prop="collapsible ? run.current : undefined"
    >
      <craft-button
        v-if="collapsible"
        slot="invoker"
        type="button"
        variant="plain"
        size="small"
        flush
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

  /* Block-level, so it fills the row even as `flush` pulls it outward. */
  .workflow-review-run__heading {
    display: flex;
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
    transform: rotate(-90deg);
    transition: transform 100ms ease;

    &:dir(rtl) {
      transform: rotate(90deg);
    }
  }

  .workflow-review-run[opened] .workflow-review-run__chevron {
    transform: none;
  }

  .workflow-review-run__content,
  .workflow-review-stages,
  .workflow-review-events {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .workflow-review-run__content {
    gap: var(--c-spacing-md);
  }

  .workflow-review-stages,
  .workflow-review-events {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  craft-disclosure > .workflow-review-run__content {
    margin-block-start: var(--c-spacing-xs);
  }

  .workflow-review-stages {
    --_stage-radius: calc(var(--c-pane-radius, var(--c-radius-md)) - 1px);
    gap: 0;
    margin-block-end: calc(-1 * var(--c-spacing-md));

    &
      > li:has(> :is(craft-disclosure:not([opened]), section))
      + li
      :deep(.workflow-review-stage > .workflow-review-stage__heading) {
      border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    }

    &
      > li:last-child
      :deep(
        :is(
          craft-disclosure.workflow-review-stage:not([opened])
            > .workflow-review-stage__heading,
          section.workflow-review-stage
            > .workflow-review-stage__heading:last-child
        )
      ) {
      --c-button-radius-end-start: var(--_stage-radius);
      --c-button-radius-end-end: var(--_stage-radius);
      border-end-start-radius: var(--_stage-radius);
      border-end-end-radius: var(--_stage-radius);
    }
  }
</style>
